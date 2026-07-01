<?php

namespace Botnetdobbs\Luminous\Generator;

class TagRegistry
{
    private array $tags = [];

    public function register(array $tagObj): void
    {
        $this->tags[$tagObj['name']] = $tagObj;
    }

    public function all(): array
    {
        return array_values($this->tags);
    }

    public function reset(): void
    {
        $this->tags = [];
    }
}
