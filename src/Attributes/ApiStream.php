<?php

namespace Botnetdobbs\Luminous\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
final class ApiStream
{
    // Non-repeatable by design: only one streaming media type is supported per endpoint.
    public function __construct(
        public readonly ?string $schema = null,
        public readonly string $mediaType = 'text/event-stream',
        public readonly int $status = 200,
        public readonly string $description = '',
        public readonly ?array $itemSchema = null,
    ) {}
}
