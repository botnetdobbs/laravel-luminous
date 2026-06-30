<?php

namespace Botnetdobbs\Luminous\Support;

use Illuminate\Contracts\Cache\Repository;

class CacheManager
{
    public function __construct(private readonly array $config) {}

    private function store(): Repository
    {
        return cache()->store($this->config['cache']['store']);
    }

    public function get(): ?array
    {
        if (! $this->config['cache']['enabled']) {
            return null;
        }

        return $this->store()->get($this->config['cache']['key']);
    }

    public function put(array $spec): void
    {
        if (! $this->config['cache']['enabled']) {
            return;
        }

        $this->store()->put($this->config['cache']['key'], $spec, (int) ($this->config['cache']['ttl'] ?? 3600));
    }

    public function flush(): void
    {
        if (! $this->config['cache']['enabled']) {
            return;
        }

        $this->store()->forget($this->config['cache']['key']);
    }
}
