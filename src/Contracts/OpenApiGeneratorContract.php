<?php

namespace Botnetdobbs\Luminous\Contracts;

interface OpenApiGeneratorContract
{
    /**
     * Build a full OpenAPI 3.2 document as an array.
     *
     * @return array<string, mixed>
     */
    public function generate(): array;
}
