<?php

namespace Botnetdobbs\Luminous\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class ApiResponseHeader
{
    public function __construct(
        public readonly int $status,
        public readonly string $name,
        public readonly string $type = 'string',
        public readonly string $description = '',
        public readonly string $format = '',
        public readonly bool $required = false,
    ) {}
}
