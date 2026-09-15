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

namespace Instride\Bundle\DataDefinitionsBundle\EventListener;

use CoreShop\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Instride\Bundle\DataDefinitionsBundle\Model\ExportDefinition;
use Instride\Bundle\DataDefinitionsBundle\Model\ImportDefinition;
use Pimcore\Model\Exception\ConfigWriteException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class WriteableListener implements EventSubscriberInterface
{
    public function definitionIsWritable(ResourceControllerEvent $event): void
    {
        $subject = $event->getSubject();

        if ($subject instanceof ImportDefinition && !$subject->isWriteable()) {
            throw new ConfigWriteException();
        }

        if ($subject instanceof ExportDefinition && !$subject->isWriteable()) {
            throw new ConfigWriteException();
        }
    }

    #[\Override]
    public static function getSubscribedEvents()
    {
        return [
            'data_definitions.import_definition.pre_save' => 'definitionIsWritable',
            'data_definitions.export_definition.pre_save' => 'definitionIsWritable',
        ];
    }
}
