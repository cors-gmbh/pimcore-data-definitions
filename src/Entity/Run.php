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
use Instride\Bundle\DataDefinitionsBundle\Run\RunStatus;

/**
 * One execution of an import or export definition.
 *
 * Params are stored as given (any JSON-serializable structure) and passed unchanged to the Importer/Exporter.
 * Progress and counters are written with plain DBAL while a run executes (see RunTracker), so a flush of the
 * entity manager never interferes with entities managed by the import itself.
 */
#[ORM\Entity]
#[ORM\Table(name: Run::TABLE_NAME)]
#[ORM\Index(name: 'idx_definition', columns: ['type', 'definition', 'created_at'])]
#[ORM\Index(name: 'idx_status', columns: ['status'])]
class Run
{
    public const TABLE_NAME = 'data_definitions_run';

    public const TYPE_IMPORT = 'import';

    public const TYPE_EXPORT = 'export';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 16)]
    private string $type;

    #[ORM\Column(type: 'integer')]
    private int $definition;

    #[ORM\Column(type: 'string', length: 32)]
    private string $status;

    #[ORM\Column(name: 'trigger_type', type: 'string', length: 32)]
    private string $trigger;

    #[ORM\Column(name: 'user_id', type: 'integer', nullable: true)]
    private ?int $userId;

    /**
     * Stored as JSON text instead of a native JSON column to keep arbitrary structures (and key order) untouched.
     */
    #[ORM\Column(type: 'text', length: 4294967295, nullable: true)]
    private ?string $params = null;

    #[ORM\Column(type: 'integer', nullable: true, options: ['unsigned' => true])]
    private ?int $total = null;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true, 'default' => 0])]
    private int $processed = 0;

    #[ORM\Column(name: 'created_count', type: 'integer', options: ['unsigned' => true, 'default' => 0])]
    private int $createdCount = 0;

    #[ORM\Column(name: 'updated_count', type: 'integer', options: ['unsigned' => true, 'default' => 0])]
    private int $updatedCount = 0;

    #[ORM\Column(name: 'skipped_count', type: 'integer', options: ['unsigned' => true, 'default' => 0])]
    private int $skippedCount = 0;

    #[ORM\Column(name: 'error_count', type: 'integer', options: ['unsigned' => true, 'default' => 0])]
    private int $errorCount = 0;

    #[ORM\Column(name: 'log_count', type: 'integer', options: ['unsigned' => true, 'default' => 0])]
    private int $logCount = 0;

    #[ORM\Column(type: 'text', length: 65535, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $hostname = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $pid = null;

    #[ORM\Column(name: 'created_at', type: 'integer', options: ['unsigned' => true])]
    private int $createdAt;

    #[ORM\Column(name: 'started_at', type: 'integer', nullable: true, options: ['unsigned' => true])]
    private ?int $startedAt = null;

    #[ORM\Column(name: 'finished_at', type: 'integer', nullable: true, options: ['unsigned' => true])]
    private ?int $finishedAt = null;

    public function __construct(
        string $type,
        int $definition,
        string $trigger,
        array $params = [],
        ?int $userId = null,
    ) {
        $this->type = $type;
        $this->definition = $definition;
        $this->trigger = $trigger;
        $this->userId = $userId;
        $this->status = RunStatus::QUEUED;
        $this->createdAt = time();
        $this->setParams($params);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'definition' => $this->definition,
            'status' => $this->status,
            'trigger' => $this->trigger,
            'userId' => $this->userId,
            'params' => $this->getParams(),
            'total' => $this->total,
            'processed' => $this->processed,
            'createdCount' => $this->createdCount,
            'updatedCount' => $this->updatedCount,
            'skippedCount' => $this->skippedCount,
            'errorCount' => $this->errorCount,
            'logCount' => $this->logCount,
            'message' => $this->message,
            'hostname' => $this->hostname,
            'pid' => $this->pid,
            'createdAt' => $this->createdAt,
            'startedAt' => $this->startedAt,
            'finishedAt' => $this->finishedAt,
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isImport(): bool
    {
        return self::TYPE_IMPORT === $this->type;
    }

    public function getDefinition(): int
    {
        return $this->definition;
    }

    public function getTrigger(): string
    {
        return $this->trigger;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getParams(): array
    {
        if (null === $this->params || '' === $this->params) {
            return [];
        }

        $decoded = json_decode($this->params, true);

        return \is_array($decoded) ? $decoded : [];
    }

    public function setParams(array $params): void
    {
        $this->params = json_encode(
            $params,
            \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_PRESERVE_ZERO_FRACTION,
        );
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function isActive(): bool
    {
        return RunStatus::isActive($this->status);
    }

    public function getTotal(): ?int
    {
        return $this->total;
    }

    public function getProcessed(): int
    {
        return $this->processed;
    }

    public function getCreatedCount(): int
    {
        return $this->createdCount;
    }

    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    public function getLogCount(): int
    {
        return $this->logCount;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): void
    {
        $this->message = null !== $message ? mb_substr($message, 0, 60000) : null;
    }

    public function getHostname(): ?string
    {
        return $this->hostname;
    }

    public function getPid(): ?int
    {
        return $this->pid;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function getStartedAt(): ?int
    {
        return $this->startedAt;
    }

    public function getFinishedAt(): ?int
    {
        return $this->finishedAt;
    }
}
