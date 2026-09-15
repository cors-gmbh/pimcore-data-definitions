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

namespace Instride\Bundle\DataDefinitionsBundle\DependencyInjection\Compiler;

use CoreShop\Component\Registry\RegisterSimpleRegistryTypePass;

final class LoaderRegistryCompilerPass extends RegisterSimpleRegistryTypePass
{
    public const LOADER_TAG = 'data_definitions.loader';

    public function __construct(
        ) {
        parent::__construct(
            'data_definitions.registry.loader',
            'data_definitions.loaders',
            self::LOADER_TAG,
        );
    }
}
