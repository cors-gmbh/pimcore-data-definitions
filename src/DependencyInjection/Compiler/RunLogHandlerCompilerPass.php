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

use Instride\Bundle\DataDefinitionsBundle\Logging\RunLogHandler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Attaches the RunLogHandler to the channel loggers used by the Importer and Exporter.
 * Has to run after MonologBundle's LoggerChannelPass created those loggers.
 */
final class RunLogHandlerCompilerPass implements CompilerPassInterface
{
    public const CHANNELS = ['import_definition', 'export_definition'];

    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(RunLogHandler::class)) {
            return;
        }

        foreach (self::CHANNELS as $channel) {
            $loggerId = 'monolog.logger.' . $channel;

            if (!$container->hasDefinition($loggerId)) {
                continue;
            }

            $container->getDefinition($loggerId)->addMethodCall('pushHandler', [new Reference(RunLogHandler::class)]);
        }
    }
}
