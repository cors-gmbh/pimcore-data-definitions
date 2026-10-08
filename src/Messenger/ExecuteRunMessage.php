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

namespace Instride\Bundle\DataDefinitionsBundle\Messenger;

/**
 * Executes a whole import/export run on a worker. The run itself (definition, params) is stored in the database.
 */
final class ExecuteRunMessage
{
    public function __construct(
        private int $runId,
    ) {
    }

    public function getRunId(): int
    {
        return $this->runId;
    }
}
