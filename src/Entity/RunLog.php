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

namespace Instride\Bundle\DataDefinitionsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A log record written during a run. Inserted in batches with DBAL by the RunLogHandler,
 * the entity exists for schema mapping and reading.
 */
#[ORM\Entity]
#[ORM\Table(name: RunLog::TABLE_NAME)]
#[ORM\Index(name: 'idx_run_level', columns: ['run_id', 'level'])]
class RunLog
{
    public const TABLE_NAME = 'data_definitions_run_log';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Run::class)]
    #[ORM\JoinColumn(name: 'run_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Run $run;

    #[ORM\Column(type: 'string', length: 16)]
    private string $level;

    #[ORM\Column(type: 'text', length: 16777215)]
    private string $message;

    #[ORM\Column(type: 'text', length: 4294967295, nullable: true)]
    private ?string $context = null;

    #[ORM\Column(name: 'row_index', type: 'integer', nullable: true, options: ['unsigned' => true])]
    private ?int $rowIndex = null;

    #[ORM\Column(name: 'created_at', type: 'integer', options: ['unsigned' => true])]
    private int $createdAt;

    public function __construct(
        Run $run,
        string $level,
        string $message,
        ?array $context = null,
        ?int $rowIndex = null,
    ) {
        $this->run = $run;
        $this->level = $level;
        $this->message = $message;
        $this->context = null !== $context ? json_encode($context, \JSON_PARTIAL_OUTPUT_ON_ERROR) ?: null : null;
        $this->rowIndex = $rowIndex;
        $this->createdAt = time();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getRun(): Run
    {
        return $this->run;
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getContext(): ?array
    {
        if (null === $this->context) {
            return null;
        }

        $decoded = json_decode($this->context, true);

        return \is_array($decoded) ? $decoded : null;
    }

    public function getRowIndex(): ?int
    {
        return $this->rowIndex;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => (int) $this->id,
            'level' => $this->level,
            'message' => $this->message,
            'context' => $this->getContext(),
            'rowIndex' => $this->rowIndex,
            'createdAt' => $this->createdAt,
        ];
    }
}
