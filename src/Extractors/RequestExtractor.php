<?php

namespace Botnetdobbs\Luminous\Extractors;

use Botnetdobbs\Luminous\Extractors\Concerns\ExtractsAnnotatedProperties;
use Botnetdobbs\Luminous\Generator\ComponentsRegistry;
use Botnetdobbs\Luminous\Support\TypeMapper;

class RequestExtractor
{
    use ExtractsAnnotatedProperties;

    public function __construct(
        private readonly TypeMapper $typeMapperInstance,
        private readonly ComponentsRegistry $registryInstance,
        private readonly EnumExtractor $enumExtractorInstance,
    ) {}

    protected function typeMapper(): TypeMapper
    {
        return $this->typeMapperInstance;
    }

    protected function registry(): ComponentsRegistry
    {
        return $this->registryInstance;
    }

    protected function enumExtractor(): EnumExtractor
    {
        return $this->enumExtractorInstance;
    }

    public function extract(string $requestClass): array
    {
        if (! class_exists($requestClass)) {
            return ['type' => 'object'];
        }

        if ($this->registryInstance->isRegistered($requestClass)) {
            return ['$ref' => $this->registryInstance->refFor($requestClass)];
        }

        $reflection = new \ReflectionClass($requestClass);
        ['properties' => $properties, 'required' => $required] = $this->extractAnnotatedProperties($reflection);

        if (empty($properties)) {
            ['properties' => $properties, 'required' => $required] = $this->extractFromRules($reflection);
        }

        $schema = ['type' => 'object', 'properties' => $properties];
        if (! empty($required)) {
            $schema['required'] = $required;
        }

        $ref = $this->registryInstance->register($requestClass, $schema);

        return ['$ref' => $ref];
    }

    private function extractFromRules(\ReflectionClass $reflection): array
    {
        try {
            $instance = $reflection->newInstanceWithoutConstructor();
            if (! method_exists($instance, 'rules')) {
                return ['properties' => [], 'required' => []];
            }

            // FormRequest::rules() may call $this->input(), $this->user(), $this->route()
            // all throw without a request context. Catch all Throwables.
            $allRules = $instance->rules();
        } catch (\Throwable) {
            return ['properties' => [], 'required' => []];
        }

        $properties = [];
        $required = [];

        collect($allRules)->each(function ($fieldRules, string $field) use (&$properties, &$required) {
            $rules = is_array($fieldRules) ? $fieldRules : explode('|', (string) $fieldRules);
            $schema = $this->typeMapperInstance->validationRulesToSchema($rules);
            $nullable = in_array('nullable', $rules, true);

            // Only one level of nesting is handled (parent.child). Deeper paths
            // (a.b.c) and wildcard segments (items.*.price) are not supported
            // and will produce a child key containing the remaining path as a literal string.
            if (str_contains($field, '.')) {
                [$parent, $child] = explode('.', $field, 2);

                if (! isset($properties[$parent]['properties'])) {
                    $properties[$parent] = ['type' => 'object', 'properties' => []];
                }

                $properties[$parent]['properties'][$child] = $schema;

                if (! $nullable && in_array('required', $rules, true)) {
                    $properties[$parent]['required'][] = $child;
                }
            } else {
                $properties[$field] = $schema;

                if (! $nullable && in_array('required', $rules, true)) {
                    $required[] = $field;
                }
            }
        });

        return compact('properties', 'required');
    }
}
