<?php

namespace Botnetdobbs\Luminous\Support;

use Composer\InstalledVersions;
use Illuminate\Contracts\Cache\Repository;

class CacheManager
{
    private ?string $fingerprint = null;

    public function __construct(private readonly array $config) {}

    private function isEnabled(): bool
    {
        return (bool) $this->config['cache']['enabled'];
    }

    private function store(): Repository
    {
        return cache()->store($this->config['cache']['store']);
    }

    /**
     * Cache key prefix from config plus a short fingerprint of package version and config.
     * Changing config or upgrading the package uses a new key; old entries expire via TTL.
     */
    public function key(): string
    {
        $base = (string) ($this->config['cache']['key'] ?? 'luminous:spec');

        return $base.':'.$this->fingerprint();
    }

    private function fingerprint(): string
    {
        if ($this->fingerprint !== null) {
            return $this->fingerprint;
        }

        $packageVersion = 'dev';
        try {
            if (class_exists(InstalledVersions::class)
                && InstalledVersions::isInstalled('botnetdobbs/laravel-luminous')) {
                $packageVersion = InstalledVersions::getPrettyVersion('botnetdobbs/laravel-luminous') ?? 'dev';
            }
        } catch (\Throwable) {
            // Package may not be installed under that name in tests / path repos.
        }

        $configForHash = $this->config;
        // UI settings never influence the generated spec, so they must not bust the cache.
        unset($configForHash['ui']);

        $payload = json_encode([
            'package' => $packageVersion,
            'config' => $configForHash,
        ], JSON_THROW_ON_ERROR);

        return $this->fingerprint = substr(hash('sha256', $payload), 0, 16);
    }

    public function get(): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        return $this->store()->get($this->key());
    }

    public function put(array $spec): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $this->store()->put($this->key(), $spec, (int) ($this->config['cache']['ttl'] ?? 3600));
    }

    public function flush(): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $this->store()->forget($this->key());
    }
}
