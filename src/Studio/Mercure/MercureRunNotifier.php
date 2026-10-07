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
use Instride\Bundle\DataDefinitionsBundle\Run\Notifier\RunNotifierInterface;
use Instride\Bundle\DataDefinitionsBundle\Run\RunNormalizer;
use Instride\Bundle\DataDefinitionsBundle\Run\RunState;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Throwable;

/**
 * Publishes run updates to the private run topics of the Studio Mercure hub.
 *
 * Payload: { dataDefinitionsRun: 'changed' | 'progress' | 'deleted', run: {...} }
 */
final class MercureRunNotifier implements RunNotifierInterface
{
    public const MESSAGE_KEY = 'dataDefinitionsRun';

    public function __construct(
        private PublishServiceInterface $publishService,
        private RunNormalizer $normalizer,
    ) {
    }

    #[\Override]
    public function runChanged(Run $run): void
    {
        $this->publish($run->getType(), 'changed', $this->normalizer->normalize($run));
    }

    #[\Override]
    public function runProgress(RunState $state): void
    {
        $this->publish($state->type, 'progress', [
            'id' => $state->runId,
            'type' => $state->type,
            'total' => $state->total,
            'processed' => $state->processed,
            'createdCount' => $state->created,
            'updatedCount' => $state->updated,
            'skippedCount' => $state->skipped,
            'errorCount' => $state->errors,
            'logCount' => $state->logCount,
            'message' => $state->message,
        ]);
    }

    #[\Override]
    public function runDeleted(Run $run): void
    {
        $this->publish($run->getType(), 'deleted', [
            'id' => $run->getId(),
            'type' => $run->getType(),
            'definition' => $run->getDefinition(),
        ]);
    }

    private function publish(string $type, string $event, array $run): void
    {
        try {
            $this->publishService->publish(
                RunTopics::forType($type),
                [self::MESSAGE_KEY => $event, 'run' => $run],
            );
        } catch (Throwable) {
            // live updates are best effort, the hub being down must never affect a run
        }
    }
}
