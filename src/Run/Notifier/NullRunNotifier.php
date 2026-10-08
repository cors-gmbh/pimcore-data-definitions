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

final class NullRunNotifier implements RunNotifierInterface
{
    #[\Override]
    public function runChanged(Run $run): void
    {
    }

    #[\Override]
    public function runProgress(RunState $state): void
    {
    }

    #[\Override]
    public function runDeleted(Run $run): void
    {
    }
}
