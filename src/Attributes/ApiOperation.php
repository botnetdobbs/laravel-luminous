<?php

namespace Botnetdobbs\Luminous\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
final class ApiOperation
{
    public function __construct(
        public readonly string $summary,
        public readonly string $description = '',
        public readonly ?string $operationId = null,
        public readonly ?string $externalDocsUrl = null,
        public readonly string $externalDocsDescription = '',
    ) {}
}
