<?php

declare(strict_types=1);

/*
 * This source file is available under two different licenses:
 *  - Data Definitions Commercial License (DDCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CORS GmbH (https://www.cors.gmbh)
 * @license    DDCL
 */

namespace Instride\Bundle\DataDefinitionsBundle\Run;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Instride\Bundle\DataDefinitionsBundle\Entity\Run;
use Instride\Bundle\DataDefinitionsBundle\Entity\RunLog;

/**
 * Reads runs through the ORM, writes progress and log records with plain DBAL.
 *
 * Writes during an execution must not flush the shared entity manager (the import itself may have pending
 * entities, or the manager may have been closed by an exception), so they go straight to the connection.
 */
final class RunRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Connection $connection,
    ) {
    }

    public function add(Run $run): void
    {
        $this->entityManager->persist($run);
        $this->entityManager->flush();
    }

    public function remove(Run $run): void
    {
        $this->entityManager->remove($run);
        $this->entityManager->flush();
    }

    public function find(int $id): ?Run
    {
        $run = $this->entityManager->find(Run::class, $id);

        if ($run instanceof Run) {
            // columns may have been changed with DBAL by a worker in the meantime
            $this->entityManager->refresh($run);
        }

        return $run;
    }

    /**
     * @return array{items: Run[], total: int}
     */
    public function findByFilter(
        ?string $type = null,
        ?int $definition = null,
        array $statuses = [],
        int $limit = 25,
        int $offset = 0,
    ): array {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('r')
            ->from(Run::class, 'r')
        ;

        if (null !== $type) {
            $queryBuilder->andWhere('r.type = :type')->setParameter('type', $type);
        }

        if (null !== $definition) {
            $queryBuilder->andWhere('r.definition = :definition')->setParameter('definition', $definition);
        }

        if ($statuses) {
            $queryBuilder->andWhere('r.status IN (:statuses)')->setParameter('statuses', $statuses);
        }

        $total = (int) (clone $queryBuilder)->select('COUNT(r.id)')->getQuery()->getSingleScalarResult();

        $items = $queryBuilder
            ->orderBy('r.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->setHint(\Doctrine\ORM\Query::HINT_REFRESH, true)
            ->getResult()
        ;

        return ['items' => $items, 'total' => $total];
    }

    public function update(int $id, array $columns): void
    {
        if (!$columns) {
            return;
        }

        $this->connection->update(Run::TABLE_NAME, $columns, ['id' => $id]);
    }

    /**
     * Atomic status transition, returns false when the run was not in one of the expected states.
     */
    public function transition(int $id, array $from, string $to, array $columns = []): bool
    {
        $assignments = ['status = ?'];
        $values = [$to];

        foreach ($columns as $column => $value) {
            $assignments[] = $column . ' = ?';
            $values[] = $value;
        }

        $affected = $this->connection->executeStatement(
            sprintf(
                'UPDATE %s SET %s WHERE id = ? AND status IN (?)',
                Run::TABLE_NAME,
                implode(', ', $assignments),
            ),
            [...$values, $id, $from],
            [...array_fill(0, \count($values), ParameterType::STRING), ParameterType::INTEGER, ArrayParameterType::STRING],
        );

        return $affected > 0;
    }

    public function getStatus(int $id): ?string
    {
        $status = $this->connection->fetchOne(
            sprintf('SELECT status FROM %s WHERE id = ?', Run::TABLE_NAME),
            [$id],
        );

        return false === $status ? null : (string) $status;
    }

    /**
     * @param array<int, array{level: string, message: string, context: ?string, row_index: ?int, created_at: int}> $records
     */
    public function insertLogs(int $runId, array $records): void
    {
        if (!$records) {
            return;
        }

        $this->connection->transactional(function (Connection $connection) use ($runId, $records): void {
            foreach ($records as $record) {
                $connection->insert(RunLog::TABLE_NAME, ['run_id' => $runId] + $record);
            }
        });
    }

    /**
     * @return array{items: array[], total: int}
     */
    public function findLogs(int $runId, array $levels = [], ?string $search = null, int $limit = 100, int $offset = 0): array
    {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->from(RunLog::TABLE_NAME)
            ->where('run_id = :run')
            ->setParameter('run', $runId)
        ;

        if ($levels) {
            $queryBuilder->andWhere('level IN (:levels)')->setParameter('levels', $levels, ArrayParameterType::STRING);
        }

        if (null !== $search && '' !== $search) {
            $queryBuilder->andWhere('message LIKE :search')->setParameter('search', '%' . addcslashes($search, '%_') . '%');
        }

        $total = (int) (clone $queryBuilder)->select('COUNT(*)')->executeQuery()->fetchOne();

        $rows = $queryBuilder
            ->select('id', 'level', 'message', 'context', 'row_index', 'created_at')
            ->orderBy('id', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        $items = array_map(static function (array $row): array {
            $context = null;
            if (\is_string($row['context']) && '' !== $row['context']) {
                $decoded = json_decode($row['context'], true);
                $context = \is_array($decoded) ? $decoded : null;
            }

            return [
                'id' => (int) $row['id'],
                'level' => $row['level'],
                'message' => $row['message'],
                'context' => $context,
                'rowIndex' => null !== $row['row_index'] ? (int) $row['row_index'] : null,
                'createdAt' => (int) $row['created_at'],
            ];
        }, $rows);

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Removes finished runs (and through the foreign key their logs) created before the given timestamp.
     */
    public function deleteFinishedBefore(int $timestamp): int
    {
        return (int) $this->connection->executeStatement(
            sprintf('DELETE FROM %s WHERE created_at < ? AND status IN (?)', Run::TABLE_NAME),
            [$timestamp, RunStatus::TERMINAL],
            [ParameterType::INTEGER, ArrayParameterType::STRING],
        );
    }
}
