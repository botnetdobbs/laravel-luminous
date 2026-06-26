<?php

namespace Botnetdobbs\Luminous\Extractors;

use Botnetdobbs\Luminous\Extractors\Concerns\ExtractsAnnotatedProperties;
use Botnetdobbs\Luminous\Generator\ComponentsRegistry;
use Botnetdobbs\Luminous\Support\TypeMapper;
use Illuminate\Http\Resources\Json\JsonResource;

class ResourceExtractor
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

    public function extract(string $resourceClass): array
    {
        if (! class_exists($resourceClass)) {
            return ['type' => 'object'];
        }

        if ($this->registryInstance->isRegistered($resourceClass)) {
            return ['$ref' => $this->registryInstance->refFor($resourceClass)];
        }

        // Pre-register a placeholder before building so that any recursive call
        // for this class hits isRegistered()=true above and breaks the cycle.
        // The idempotency check doubles as the cycle guard, and the ref it returns
        // uses the registry's disambiguated name rather than a raw class_basename().
        $ref = $this->registryInstance->register($resourceClass, ['type' => 'object']);

        $schema = $this->buildSchema($resourceClass);

        // Replace placeholder with the complete schema now that recursion has unwound.
        $this->registryInstance->updateSchema($resourceClass, $schema);

        return ['$ref' => $ref];
    }

    private function buildSchema(string $resourceClass): array
    {
        $reflection = new \ReflectionClass($resourceClass);
        ['properties' => $properties, 'required' => $required] = $this->extractAnnotatedProperties($reflection);

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            $reflType = $prop->getType();
            $phpType = $reflType instanceof \ReflectionNamedType ? $reflType->getName() : '';

            if ($phpType && class_exists($phpType) && is_subclass_of($phpType, JsonResource::class)) {
                // Annotation wins: only recurse if no #[ApiProperty] already produced a schema.
                if (! isset($properties[$prop->getName()])) {
                    $properties[$prop->getName()] = $this->extract($phpType);
                }
            }
        }

        $schema = ['type' => 'object', 'properties' => $properties];
        if (! empty($required)) {
            $schema['required'] = $required;
        }

        return $schema;
    }
}
