<?php

namespace Botnetdobbs\Luminous\Tests\Feature;

use Botnetdobbs\Luminous\Support\CacheManager;
use Botnetdobbs\Luminous\Tests\LuminousTestCase;

class CacheManagerTest extends LuminousTestCase
{
    private function makeManager(array $cacheConfig): CacheManager
    {
        return new CacheManager(['cache' => $cacheConfig]);
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
}
