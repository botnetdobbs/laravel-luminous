<?php

namespace Botnetdobbs\Luminous\Tests\Feature;

use Botnetdobbs\Luminous\Tests\LuminousTestCase;

class HttpLayerDisabledTest extends LuminousTestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('luminous.enabled', false);
    }

    public function test_routes_disabled_when_enabled_false(): void
    {
        $this->get('/docs/openapi.json')->assertStatus(404);
        $this->get('/docs/openapi.yaml')->assertStatus(404);
        $this->get('/docs')->assertStatus(404);
    }
}
