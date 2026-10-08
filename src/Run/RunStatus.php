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

final class RunStatus
{
    public const QUEUED = 'queued';

    public const RUNNING = 'running';

    public const STOPPING = 'stopping';

    public const FINISHED = 'finished';

    public const FINISHED_WITH_ERRORS = 'finished_with_errors';

    public const FAILED = 'failed';

    public const CANCELLED = 'cancelled';

    public const ACTIVE = [self::QUEUED, self::RUNNING, self::STOPPING];

    public const TERMINAL = [self::FINISHED, self::FINISHED_WITH_ERRORS, self::FAILED, self::CANCELLED];

    public static function isActive(string $status): bool
    {
        return \in_array($status, self::ACTIVE, true);
    }
}
