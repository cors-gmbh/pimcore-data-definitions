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

/**
 * Describes one run parameter for the "start run" form in Studio.
 *
 * Supported types: text, textarea, number, boolean, select, multiselect, date, asset, object, json.
 * "asset" renders a path field with an upload button, the uploaded file is stored as asset and its path is used.
 * Parameters not described by any schema can always be passed through the free JSON editor.
 */
final class ParamField
{
    public const TYPES = ['text', 'textarea', 'number', 'boolean', 'select', 'multiselect', 'date', 'asset', 'object', 'json'];

    /**
     * @param array<int, array{value: mixed, label: string}>|array<string, string> $options
     */
    public function __construct(
        public readonly string $name,
        public readonly string $type = 'text',
        public readonly ?string $label = null,
        public readonly bool $required = false,
        public readonly mixed $default = null,
        public readonly ?string $description = null,
        public readonly array $options = [],
        public readonly ?string $group = null,
    ) {
        if (!\in_array($type, self::TYPES, true)) {
            throw new \InvalidArgumentException(sprintf('Invalid param type "%s" for "%s", allowed: %s', $type, $name, implode(', ', self::TYPES)));
        }
    }

    public static function fromArray(array $field): self
    {
        if (!isset($field['name']) || !\is_string($field['name']) || '' === $field['name']) {
            throw new \InvalidArgumentException('A param field needs a "name"');
        }

        return new self(
            $field['name'],
            $field['type'] ?? 'text',
            $field['label'] ?? null,
            (bool) ($field['required'] ?? false),
            $field['default'] ?? null,
            $field['description'] ?? null,
            $field['options'] ?? [],
            $field['group'] ?? null,
        );
    }

    public function toArray(): array
    {
        $options = [];
        foreach ($this->options as $key => $option) {
            $options[] = \is_array($option) ? $option : ['value' => \is_string($key) ? $key : $option, 'label' => (string) $option];
        }

        return [
            'name' => $this->name,
            'type' => $this->type,
            'label' => $this->label ?? $this->name,
            'required' => $this->required,
            'default' => $this->default,
            'description' => $this->description,
            'options' => $options,
            'group' => $this->group,
        ];
    }
}
