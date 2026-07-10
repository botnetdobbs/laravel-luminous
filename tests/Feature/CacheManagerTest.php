<?php

namespace Botnetdobbs\Luminous\Tests\Feature;

use Botnetdobbs\Luminous\Support\CacheManager;
use Botnetdobbs\Luminous\Tests\LuminousTestCase;

class CacheManagerTest extends LuminousTestCase
{
    private function makeManager(array $cacheConfig, array $extraConfig = []): CacheManager
    {
        return new CacheManager(array_merge([
            'cache' => $cacheConfig,
            'info' => ['title' => 'Test', 'version' => '1.0.0'],
        ], $extraConfig));
    }

    public function test_get_returns_null_when_cache_disabled(): void
    {
        $manager = $this->makeManager(['enabled' => false, 'store' => null, 'key' => 'luminous', 'ttl' => 3600]);

        $this->assertNull($manager->get());
    }

    public function test_put_and_get_store_and_retrieve_spec(): void
    {
        $manager = $this->makeManager(['enabled' => true, 'store' => 'array', 'key' => 'luminous_test', 'ttl' => 60]);
        $spec = ['openapi' => '3.2.0', 'info' => ['title' => 'Test']];

        $manager->put($spec);

        $this->assertSame($spec, $manager->get());
    }

    public function test_flush_removes_cached_spec(): void
    {
        $manager = $this->makeManager(['enabled' => true, 'store' => 'array', 'key' => 'luminous_test', 'ttl' => 60]);
        $manager->put(['openapi' => '3.2.0']);

        $manager->flush();

        $this->assertNull($manager->get());
    }

    public function test_put_is_noop_when_cache_disabled(): void
    {
        $manager = $this->makeManager(['enabled' => false, 'store' => 'array', 'key' => 'luminous_test', 'ttl' => 60]);
        $manager->put(['openapi' => '3.2.0']);

        $this->assertNull($manager->get());
    }

    public function test_flush_is_noop_when_cache_disabled(): void
    {
        $enabledManager = $this->makeManager(['enabled' => true, 'store' => 'array', 'key' => 'luminous_test', 'ttl' => 60]);
        $enabledManager->put(['openapi' => '3.2.0']);

        $disabledManager = $this->makeManager(['enabled' => false, 'store' => 'array', 'key' => 'luminous_test', 'ttl' => 60]);
        $disabledManager->flush();

        $this->assertSame(['openapi' => '3.2.0'], $enabledManager->get());
    }

    public function test_key_includes_base_prefix_and_fingerprint(): void
    {
        $manager = $this->makeManager(['enabled' => true, 'store' => 'array', 'key' => 'luminous:spec', 'ttl' => 60]);

        $key = $manager->key();

        $this->assertStringStartsWith('luminous:spec:', $key);
        $this->assertMatchesRegularExpression('/^luminous:spec:[a-f0-9]{16}$/', $key);
    }

    public function test_different_config_produces_different_keys(): void
    {
        $a = $this->makeManager(
            ['enabled' => true, 'store' => 'array', 'key' => 'luminous:spec', 'ttl' => 60],
            ['info' => ['title' => 'API A', 'version' => '1.0.0']],
        );
        $b = $this->makeManager(
            ['enabled' => true, 'store' => 'array', 'key' => 'luminous:spec', 'ttl' => 60],
            ['info' => ['title' => 'API B', 'version' => '1.0.0']],
        );

        $this->assertNotSame($a->key(), $b->key());
    }

    public function test_config_change_does_not_read_stale_entry_from_other_fingerprint(): void
    {
        $store = 'array';
        $base = ['enabled' => true, 'store' => $store, 'key' => 'luminous:stale', 'ttl' => 60];

        $first = $this->makeManager($base, ['info' => ['title' => 'Before', 'version' => '1.0.0']]);
        $first->put(['openapi' => '3.2.0', 'mark' => 'old']);

        $second = $this->makeManager($base, ['info' => ['title' => 'After', 'version' => '1.0.0']]);

        $this->assertNull($second->get(), 'New config fingerprint must miss the previous cache entry');
        $this->assertSame(['openapi' => '3.2.0', 'mark' => 'old'], $first->get());
    }
}
