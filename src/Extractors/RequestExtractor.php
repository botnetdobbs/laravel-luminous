<?php

namespace Botnetdobbs\Luminous\Extractors;

use Botnetdobbs\Luminous\Extractors\Concerns\ExtractsAnnotatedProperties;
use Botnetdobbs\Luminous\Generator\ComponentsRegistry;
use Botnetdobbs\Luminous\Support\TypeMapper;

class RequestExtractor
{
    use ExtractsAnnotatedProperties;

    public function __construct(
        private readonly TypeMapper $typeMapper,
        private readonly ComponentsRegistry $registry,
        private readonly EnumExtractor $enumExtractor,
        private readonly RulesSchemaBuilder $rulesBuilder,
    ) {}

    public function extract(string $requestClass): array
    {
        if (! class_exists($requestClass)) {
            return ['type' => 'object'];
        }

        if ($this->registry->isRegistered($requestClass)) {
            return ['$ref' => $this->registry->refFor($requestClass)];
        }

        $schema = $this->buildSchema($requestClass);
        $this->registry->register($requestClass, $schema);

        return ['$ref' => $this->registry->refFor($requestClass)];
    }

    /**
     * Determine the request media type.
     * Returns 'multipart/form-data' when any rule contains file/image/mimes keywords.
     */
    public function mediaType(string $requestClass): string
    {
        if (! class_exists($requestClass)) {
            return 'application/json';
        }

        try {
            $instance = (new \ReflectionClass($requestClass))->newInstanceWithoutConstructor();
            if (! method_exists($instance, 'rules')) {
                return 'application/json';
            }

            foreach ($instance->rules() as $fieldRules) {
                $ruleArray = $this->rulesBuilder->normaliseRules($fieldRules);
                foreach ($ruleArray as $rule) {
                    if (is_string($rule) && in_array($rule, ['file', 'image', 'mimes', 'mimetypes'], true)) {
                        return 'multipart/form-data';
                    }
                    if (is_string($rule) && (str_starts_with($rule, 'mimes:') || str_starts_with($rule, 'mimetypes:'))) {
                        return 'multipart/form-data';
                    }
                }
            }
        } catch (\Throwable $e) {
            logger()->debug("Luminous: mediaType() could not call rules() on [{$requestClass}]: {$e->getMessage()}");
        }

        return 'application/json';
    }

    private function buildSchema(string $requestClass): array
    {
        $reflection = new \ReflectionClass($requestClass);

        // Strategy 1: #[ApiShape] static schema() method
        $shapeResult = $this->tryShapeStrategy($requestClass, $reflection, fn ($s) => $this->resolveShapeEnumRefs($s));
        if ($shapeResult !== null) {
            return $shapeResult;
        }

        // Strategy 2: public properties with #[ApiProperty] + hints() overlay
        ['properties' => $properties, 'required' => $required] = $this->extractAnnotatedProperties($reflection, $this->typeMapper, $this->registry, $this->enumExtractor);

        if (! empty($properties)) {
            $hints = $this->rulesBuilder->loadHints($reflection);
            foreach ($hints as $field => $hint) {
                if (isset($properties[$field])) {
                    $properties[$field] = $this->rulesBuilder->mergeHint($properties[$field], $hint);
                }
            }

            $schema = ['type' => 'object', 'properties' => $properties];
            if (! empty($required)) {
                $schema['required'] = array_values($required);
            }

            return $schema;
        }

        // Strategy 3: rules() + hints()
        return $this->rulesBuilder->build($reflection);
    }

    /**
     * Recursively resolve BackedEnum class-name refs in a Shape-produced schema.
     * Resource class refs are NOT resolved here. FormRequests don't reference resources.
     */
    private function resolveShapeEnumRefs(array $schema): array
    {
        if (isset($schema['$ref'])) {
            $ref = $schema['$ref'];
            if (is_string($ref) && class_exists($ref) && is_subclass_of($ref, \BackedEnum::class)) {
                $schema['$ref'] = self::registerEnumRef($ref, $this->registry, $this->enumExtractor);
            }

            return $schema;
        }

        foreach ($schema as $key => $value) {
            if (is_array($value)) {
                $schema[$key] = $this->resolveShapeEnumRefs($value);
            }
        }

        return $schema;
    }
}
