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

use Instride\Bundle\DataDefinitionsBundle\Event\DefinitionEventInterface;
use Instride\Bundle\DataDefinitionsBundle\Run\RunTracker;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Feeds the import/export events into the RunTracker. Child events (".child") of nested imports are ignored,
 * they belong to the parent row.
 */
final class RunTrackingSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RunTracker $tracker,
    ) {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        $events = [];

        foreach (['import', 'export'] as $type) {
            $events[sprintf('data_definitions.%s.total', $type)] = 'onTotal';
            $events[sprintf('data_definitions.%s.status', $type)] = 'onStatus';
            $events[sprintf('data_definitions.%s.object.start', $type)] = 'onObjectStart';
            $events[sprintf('data_definitions.%s.object.skipped', $type)] = 'onObjectSkipped';
            $events[sprintf('data_definitions.%s.object.finished', $type)] = 'onObjectFinished';
            $events[sprintf('data_definitions.%s.failure', $type)] = 'onFailure';
            $events[sprintf('data_definitions.%s.progress', $type)] = 'onProgress';
        }

        return $events;
    }

    public function onTotal(DefinitionEventInterface $event): void
    {
        $subject = $event->getSubject();

        if (is_numeric($subject)) {
            $this->tracker->onTotal((int) $subject);
        }
    }

    public function onStatus(DefinitionEventInterface $event): void
    {
        $subject = $event->getSubject();

        if (\is_string($subject)) {
            $this->tracker->onStatus($subject);
        }
    }

    public function onObjectStart(DefinitionEventInterface $event): void
    {
        $subject = $event->getSubject();

        $this->tracker->onObjectStart($subject instanceof ElementInterface && !$subject->getId());
    }

    public function onObjectSkipped(DefinitionEventInterface $event): void
    {
        $subject = $event->getSubject();

        $this->tracker->onObjectSkipped(\is_string($subject) ? $subject : null);
    }

    public function onObjectFinished(DefinitionEventInterface $event): void
    {
        $this->tracker->onObjectFinished();
    }

    public function onFailure(DefinitionEventInterface $event): void
    {
        $subject = $event->getSubject();

        $this->tracker->onFailure(\is_string($subject) ? $subject : null);
    }

    public function onProgress(DefinitionEventInterface $event): void
    {
        $this->tracker->onProgress();
    }
}
