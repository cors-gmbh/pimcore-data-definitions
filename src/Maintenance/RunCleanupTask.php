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

namespace Instride\Bundle\DataDefinitionsBundle\Maintenance;

use Instride\Bundle\DataDefinitionsBundle\Run\RunRepository;
use Pimcore\Maintenance\TaskInterface;

/**
 * Removes finished runs (and their logs) older than data_definitions.runs.retention_days.
 */
final class RunCleanupTask implements TaskInterface
{
    public function __construct(
        private RunRepository $repository,
        private int $retentionDays,
    ) {
    }

    #[\Override]
    public function execute(): void
    {
        if ($this->retentionDays <= 0) {
            return;
        }

        $this->repository->deleteFinishedBefore(time() - $this->retentionDays * 86400);
    }
}
