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

/**
 * In-memory state of the run executing in this process.
 *
 * @internal
 */
final class RunState
{
    public ?int $total = null;

    public int $processed = 0;

    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public int $errors = 0;

    public int $logCount = 0;

    public int $droppedLogs = 0;

    public bool $hasLoggedErrors = false;

    public ?string $message = null;

    public bool $dirty = false;

    public bool $stopRequested = false;

    public float $lastFlush = 0.0;

    public array $logBuffer = [];

    public bool $rowIsNew = false;

    public bool $rowFinished = false;

    public bool $rowSkipped = false;

    public bool $rowFailed = false;

    public function __construct(
        public readonly int $runId,
        public readonly string $type,
    ) {
    }

    public function resetRow(): void
    {
        $this->rowIsNew = false;
        $this->rowFinished = false;
        $this->rowSkipped = false;
        $this->rowFailed = false;
    }
}
