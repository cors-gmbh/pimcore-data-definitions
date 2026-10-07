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

use Instride\Bundle\DataDefinitionsBundle\Model\DataDefinitionInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched as "data_definitions.run.params_schema" after the schema of all services of a definition was
 * collected. Projects can add, replace or remove fields per definition.
 */
final class ParamsSchemaEvent extends Event
{
    /**
     * @param array<string, ParamField> $fields
     */
    public function __construct(
        private DataDefinitionInterface $definition,
        private string $type,
        private array $fields,
    ) {
    }

    public function getDefinition(): DataDefinitionInterface
    {
        return $this->definition;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array<string, ParamField>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function addField(ParamField|array $field): void
    {
        $field = $field instanceof ParamField ? $field : ParamField::fromArray($field);
        $this->fields[$field->name] = $field;
    }

    public function removeField(string $name): void
    {
        unset($this->fields[$name]);
    }
}
