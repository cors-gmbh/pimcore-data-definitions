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

namespace Instride\Bundle\DataDefinitionsBundle\Studio\Mercure;

use Instride\Bundle\DataDefinitionsBundle\Entity\Run;

/**
 * Private Mercure topics with run updates, one per run type (mirrored in the Studio plugin).
 */
final class RunTopics
{
    public const PREFIX = 'data-definitions/runs/';

    public const PERMISSIONS = [
        Run::TYPE_IMPORT => 'data_definitions_permission_data_definitions_import',
        Run::TYPE_EXPORT => 'data_definitions_permission_data_definitions_export',
    ];

    public static function forType(string $type): string
    {
        return self::PREFIX . $type;
    }
}
