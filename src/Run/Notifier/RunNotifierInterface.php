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

namespace Instride\Bundle\DataDefinitionsBundle\Run\Notifier;

use Instride\Bundle\DataDefinitionsBundle\Entity\Run;
use Instride\Bundle\DataDefinitionsBundle\Run\RunState;

/**
 * Pushes run changes to clients (Studio: Mercure). Implementations must never throw,
 * a failing notification must not affect the run.
 */
interface RunNotifierInterface
{
    /**
     * Created, status changed, finished.
     */
    public function runChanged(Run $run): void;

    /**
     * Progress, counters and log count of the executing run (throttled by the RunTracker).
     */
    public function runProgress(RunState $state): void;

    public function runDeleted(Run $run): void;
}
