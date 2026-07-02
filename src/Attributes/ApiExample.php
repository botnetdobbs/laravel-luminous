<?php

namespace Botnetdobbs\Luminous\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class ApiExample
{
    public function __construct(
        public readonly string $name,
        public readonly string $summary = '',
        public readonly mixed $value = [],
        public readonly string $type = 'request',
        public readonly int $status = 200,
        public readonly string $mediaType = 'application/json',
        public readonly string $description = '',
        public readonly ?string $externalValue = null,
        public readonly mixed $dataValue = null,
        public readonly mixed $serializedValue = null,
    ) {
        if (! in_array($type, ['request', 'response'], true)) {
            throw new \InvalidArgumentException(
                "ApiExample: \$type must be 'request' or 'response', got '{$type}'"
            );
        }

        if ($dataValue !== null && $value !== []) {
            throw new \InvalidArgumentException(
                'ApiExample: $dataValue and $value are mutually exclusive. Clear $value when using $dataValue.'
            );
        }

        if ($serializedValue !== null && $value !== []) {
            throw new \InvalidArgumentException(
                'ApiExample: $serializedValue and $value are mutually exclusive. Clear $value when using $serializedValue.'
            );
        }

        if ($serializedValue !== null && $externalValue !== null) {
            throw new \InvalidArgumentException(
                'ApiExample: $serializedValue and $externalValue are mutually exclusive.'
            );
        }
    }

    public function toExampleObject(): array
    {
        $obj = ['summary' => $this->summary];

        if ($this->description !== '') {
            $obj['description'] = $this->description;
        }

        if ($this->serializedValue !== null) {
            $obj['serializedValue'] = $this->serializedValue;
        } elseif ($this->dataValue !== null) {
            $obj['dataValue'] = $this->dataValue;
        } elseif ($this->externalValue !== null) {
            $obj['externalValue'] = $this->externalValue;
        } else {
            $obj['value'] = $this->value;
        }

        return $obj;
    }
}
