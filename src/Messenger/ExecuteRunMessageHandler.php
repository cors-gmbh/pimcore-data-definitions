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

use Instride\Bundle\DataDefinitionsBundle\Run\RunManager;

final class ExecuteRunMessageHandler
{
    public function __construct(
        private RunManager $runManager,
    ) {
    }

    public function __invoke(ExecuteRunMessage $message): void
    {
        // failures end up as run status "failed", nothing is rethrown so a run is never retried by the transport
        $this->runManager->execute($message->getRunId());
    }
}
