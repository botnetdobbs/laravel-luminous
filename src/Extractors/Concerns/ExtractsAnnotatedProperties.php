<?php

namespace Botnetdobbs\Luminous\Extractors\Concerns;

use Botnetdobbs\Luminous\Attributes\ApiIgnore;
use Botnetdobbs\Luminous\Attributes\ApiItems;
use Botnetdobbs\Luminous\Attributes\ApiProperty;
use Botnetdobbs\Luminous\Extractors\EnumExtractor;
use Botnetdobbs\Luminous\Generator\ComponentsRegistry;
use Botnetdobbs\Luminous\Support\TypeMapper;

trait ExtractsAnnotatedProperties
{
    abstract protected function typeMapper(): TypeMapper;

    abstract protected function registry(): ComponentsRegistry;

    abstract protected function enumExtractor(): EnumExtractor;

    protected function extractAnnotatedProperties(\ReflectionClass $reflection): array
    {
        $properties = [];
        $required = [];

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            $attrs = $prop->getAttributes(ApiProperty::class);
            if (empty($attrs)) {
                continue;
            }
            if (! empty($prop->getAttributes(ApiIgnore::class))) {
                continue;
            }

            /** @var ApiProperty $apiProp */
            $apiProp = $attrs[0]->newInstance();
            $reflType = $prop->getType();
            $phpType = $reflType instanceof \ReflectionNamedType ? $reflType->getName() : 'mixed';
            $nullable = ($reflType?->allowsNull() ?? false) || $apiProp->nullable;

            if ($apiProp->ref) {
                $properties[$prop->getName()] = ['$ref' => $apiProp->ref];
                if (! $nullable) {
                    $required[] = $prop->getName();
                }

                continue;
            }

            if (class_exists($phpType) && is_subclass_of($phpType, \BackedEnum::class)) {
                $enumSchema = $this->enumExtractor()->extract($phpType);
                $ref = $this->registry()->register($phpType, $enumSchema);
                $properties[$prop->getName()] = ['$ref' => $ref];
                if (! $nullable) {
                    $required[] = $prop->getName();
                }

                continue;
            }

            $schema = $this->typeMapper()->phpTypeToOpenApi($phpType, $apiProp->format);

            if ($apiProp->description) {
                $schema['description'] = $apiProp->description;
            }
            if ($apiProp->example !== null) {
                $schema['example'] = $apiProp->example;
            }
            if ($nullable) {
                $schema['nullable'] = true;
            }
            if ($apiProp->minimum !== null) {
                $schema['minimum'] = $apiProp->minimum;
            }
            if ($apiProp->maximum !== null) {
                $schema['maximum'] = $apiProp->maximum;
            }
            if ($apiProp->minLength !== null) {
                $schema['minLength'] = $apiProp->minLength;
            }
            if ($apiProp->maxLength !== null) {
                $schema['maxLength'] = $apiProp->maxLength;
            }
            if (! empty($apiProp->enum)) {
                $schema['enum'] = $apiProp->enum;
            }
            if ($apiProp->readOnly) {
                $schema['readOnly'] = true;
            }
            if ($apiProp->writeOnly) {
                $schema['writeOnly'] = true;
            }

            if (($schema['type'] ?? '') === 'array') {
                $schema['items'] = $this->resolveArrayItems($prop, $apiProp);
            }

            $properties[$prop->getName()] = $schema;
            if (! $nullable) {
                $required[] = $prop->getName();
            }
        }

        return compact('properties', 'required');
    }

    private function resolveArrayItems(\ReflectionProperty $prop, ApiProperty $apiProp): array
    {
        $itemsAttr = $prop->getAttributes(ApiItems::class);
        if (! empty($itemsAttr)) {
            $items = $itemsAttr[0]->newInstance();
            if ($items->ref) {
                return ['$ref' => $items->ref];
            }
            if ($items->type) {
                return collect(['type' => $items->type, 'format' => $items->format ?: null, 'enum' => $items->enum ?: null])
                    ->filter(fn ($v) => $v !== null && $v !== [])
                    ->all();
            }
        }

        if ($apiProp->itemsRef) {
            return ['$ref' => $apiProp->itemsRef];
        }

        if ($apiProp->itemsType) {
            return ['type' => $apiProp->itemsType];
        }

        return ['type' => 'string'];
    }
}
