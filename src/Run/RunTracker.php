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

use Instride\Bundle\DataDefinitionsBundle\Entity\Run;
use Instride\Bundle\DataDefinitionsBundle\Run\Notifier\RunNotifierInterface;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Throwable;

/**
 * Collects progress, counters and log records of the run executing in this process and persists them
 * in throttled batches. Fed by the RunTrackingSubscriber (events) and the RunLogHandler (log records).
 */
final class RunTracker
{
    private const FLUSH_INTERVAL = 2.0;

    private const LOG_BUFFER_SIZE = 100;

    private const MAX_MESSAGE_LENGTH = 60000;

    /**
     * @var RunState[]
     */
    private array $stack = [];

    public function __construct(
        private RunRepository $repository,
        private EventDispatcherInterface $eventDispatcher,
        private RunNotifierInterface $notifier,
        private int $maxLogEntries = 50000,
    ) {
    }

    public function begin(Run $run): void
    {
        $this->stack[] = new RunState((int) $run->getId(), $run->getType());
    }

    /**
     * Ends tracking of the current run, persists everything still buffered and returns its final state.
     */
    public function end(): ?RunState
    {
        $state = $this->current();

        if (null === $state) {
            return null;
        }

        $this->flush(true);
        array_pop($this->stack);

        return $state;
    }

    public function isActive(): bool
    {
        return null !== $this->current();
    }

    public function current(): ?RunState
    {
        $state = end($this->stack);

        return false === $state ? null : $state;
    }

    public function onTotal(int $total): void
    {
        if ($state = $this->current()) {
            $state->total = $total;
            $this->flush(true);
        }
    }

    public function onStatus(?string $message): void
    {
        if (($state = $this->current()) && null !== $message && '' !== $message) {
            $state->message = $message;
            $state->dirty = true;
        }
    }

    public function onObjectStart(bool $isNew): void
    {
        if ($state = $this->current()) {
            $state->rowIsNew = $isNew;
        }
    }

    public function onObjectSkipped(?string $reason = null): void
    {
        if ($state = $this->current()) {
            $state->rowSkipped = true;
            $this->onStatus($reason);
        }
    }

    public function onObjectFinished(): void
    {
        if ($state = $this->current()) {
            $state->rowFinished = true;
        }
    }

    public function onFailure(?string $message): void
    {
        if ($state = $this->current()) {
            $state->rowFailed = true;
            $this->onStatus($message);
        }
    }

    /**
     * Called once per processed row, books the row into the counters.
     */
    public function onProgress(): void
    {
        $state = $this->current();

        if (null === $state) {
            return;
        }

        ++$state->processed;

        if ($state->rowFailed) {
            ++$state->errors;
        } elseif ($state->rowSkipped || !$state->rowFinished) {
            ++$state->skipped;
        } elseif ($state->rowIsNew || Run::TYPE_EXPORT === $state->type) {
            ++$state->created;
        } else {
            ++$state->updated;
        }

        $state->resetRow();
        $state->dirty = true;

        $this->flush();
    }

    public function addLog(string $level, string $message, array $context = []): void
    {
        $state = $this->current();

        if (null === $state) {
            return;
        }

        if (\in_array($level, [LogLevel::ERROR, LogLevel::CRITICAL, LogLevel::ALERT, LogLevel::EMERGENCY], true)) {
            $state->hasLoggedErrors = true;
        }

        if ($this->maxLogEntries > 0 && $state->logCount >= $this->maxLogEntries) {
            ++$state->droppedLogs;

            return;
        }

        ++$state->logCount;
        $state->logBuffer[] = [
            'level' => $level,
            'message' => mb_substr($message, 0, self::MAX_MESSAGE_LENGTH),
            'context' => $context ? (json_encode(self::normalize($context), \JSON_PARTIAL_OUTPUT_ON_ERROR | \JSON_UNESCAPED_UNICODE) ?: null) : null,
            'row_index' => $state->processed,
            'created_at' => time(),
        ];

        if (\count($state->logBuffer) >= self::LOG_BUFFER_SIZE) {
            $this->flushLogs($state);
        }
    }

    public function isStopRequested(): bool
    {
        return (bool) $this->current()?->stopRequested;
    }

    public function flush(bool $force = false): void
    {
        $state = $this->current();

        if (null === $state) {
            return;
        }

        $now = microtime(true);

        if (!$force && ($now - $state->lastFlush) < self::FLUSH_INTERVAL) {
            return;
        }

        $state->lastFlush = $now;

        $hadLogs = [] !== $state->logBuffer;
        $this->flushLogs($state);

        if ($state->dirty || $force || $hadLogs) {
            $this->repository->update($state->runId, [
                'total' => $state->total,
                'processed' => $state->processed,
                'created_count' => $state->created,
                'updated_count' => $state->updated,
                'skipped_count' => $state->skipped,
                'error_count' => $state->errors,
                'log_count' => $state->logCount,
                'message' => null !== $state->message ? mb_substr($state->message, 0, self::MAX_MESSAGE_LENGTH) : null,
            ]);
            $state->dirty = false;

            $this->notifier->runProgress($state);
        }

        $this->checkStop($state);
    }

    private function flushLogs(RunState $state): void
    {
        if (!$state->logBuffer) {
            return;
        }

        $records = $state->logBuffer;
        $state->logBuffer = [];

        $this->repository->insertLogs($state->runId, $records);
    }

    private function checkStop(RunState $state): void
    {
        if ($state->stopRequested) {
            return;
        }

        if (RunStatus::STOPPING !== $this->repository->getStatus($state->runId)) {
            return;
        }

        $state->stopRequested = true;
        $state->message = 'Stop requested';

        // Importer and Exporter both listen to this event and finish after the current row
        $this->eventDispatcher->dispatch(new Event(), 'data_definitions.stop');
    }

    private static function normalize(mixed $value, int $depth = 0): mixed
    {
        if ($depth > 5) {
            return '[...]';
        }

        if ($value instanceof Throwable) {
            return [
                'class' => $value::class,
                'message' => $value->getMessage(),
                'code' => $value->getCode(),
                'file' => $value->getFile() . ':' . $value->getLine(),
                'trace' => mb_substr($value->getTraceAsString(), 0, 10000),
            ];
        }

        if (\is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $result[$key] = self::normalize($item, $depth + 1);
            }

            return $result;
        }

        if (\is_object($value)) {
            if ($value instanceof \JsonSerializable) {
                return self::normalize($value->jsonSerialize(), $depth + 1);
            }

            if ($value instanceof \Stringable) {
                return (string) $value;
            }

            if ($value instanceof \DateTimeInterface) {
                return $value->format(\DATE_ATOM);
            }

            return '[object ' . $value::class . ']';
        }

        if (\is_resource($value)) {
            return '[resource]';
        }

        return $value;
    }
}
