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

namespace Instride\Bundle\DataDefinitionsBundle\Run\ParamsSchema;

use CoreShop\Component\Registry\ServiceRegistryInterface;
use Instride\Bundle\DataDefinitionsBundle\Entity\Run;
use Instride\Bundle\DataDefinitionsBundle\Model\DataDefinitionInterface;
use Instride\Bundle\DataDefinitionsBundle\Model\ExportDefinitionInterface;
use Instride\Bundle\DataDefinitionsBundle\Model\ExportMapping;
use Instride\Bundle\DataDefinitionsBundle\Model\ImportDefinitionInterface;
use Instride\Bundle\DataDefinitionsBundle\Model\ImportMapping;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Collects the run params schema of every service a definition uses, then lets projects adjust it via event.
 */
final class ParamsSchemaBuilder
{
    public const EVENT = 'data_definitions.run.params_schema';

    /**
     * @param array<string, ServiceRegistryInterface> $registries keyed by registry name (provider, runner, ...)
     */
    public function __construct(
        private array $registries,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @return array<int, array>
     */
    public function build(DataDefinitionInterface $definition): array
    {
        $type = $definition instanceof ExportDefinitionInterface ? Run::TYPE_EXPORT : Run::TYPE_IMPORT;
        $fields = [];

        foreach ($this->getServices($definition) as $service) {
            if (!$service instanceof ParamsSchemaProviderInterface) {
                continue;
            }

            foreach ($service->getParamsSchema($definition) as $field) {
                $field = $field instanceof ParamField ? $field : ParamField::fromArray($field);
                $fields[$field->name] ??= $field;
            }
        }

        $event = new ParamsSchemaEvent($definition, $type, $fields);
        $this->eventDispatcher->dispatch($event, self::EVENT);

        return array_values(array_map(static fn (ParamField $field): array => $field->toArray(), $event->getFields()));
    }

    /**
     * @return iterable<object>
     */
    private function getServices(DataDefinitionInterface $definition): iterable
    {
        if ($definition instanceof ImportDefinitionInterface) {
            yield from $this->get('provider', $definition->getProvider());
            yield from $this->get('loader', $definition->getLoader());
            yield from $this->get('filter', $definition->getFilter());
            yield from $this->get('runner', $definition->getRunner());
            yield from $this->get('cleaner', $definition->getCleaner());
            yield from $this->get('persister', $definition->getPersister());

            foreach ($definition->getMapping() ?? [] as $mapping) {
                if ($mapping instanceof ImportMapping) {
                    yield from $this->get('interpreter', $mapping->getInterpreter());
                    yield from $this->get('setter', $mapping->getSetter());
                }
            }

            return;
        }

        if ($definition instanceof ExportDefinitionInterface) {
            yield from $this->get('fetcher', $definition->getFetcher());
            yield from $this->get('export_provider', $definition->getProvider());
            yield from $this->get('export_runner', $definition->getRunner());

            foreach ($definition->getMapping() ?? [] as $mapping) {
                if ($mapping instanceof ExportMapping) {
                    yield from $this->get('getter', $mapping->getGetter());
                    yield from $this->get('interpreter', $mapping->getInterpreter());
                }
            }
        }
    }

    private function get(string $registry, mixed $identifier): iterable
    {
        if (!\is_string($identifier) || '' === $identifier || !isset($this->registries[$registry])) {
            return;
        }

        if ($this->registries[$registry]->has($identifier)) {
            yield $this->registries[$registry]->get($identifier);
        }
    }
}
