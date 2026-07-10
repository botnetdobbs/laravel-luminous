<?php

namespace Botnetdobbs\Luminous\Tests;

use Botnetdobbs\Luminous\Generator\ComponentsRegistry;
use Botnetdobbs\Luminous\Generator\GeneratorFactory;
use Botnetdobbs\Luminous\Generator\OpenApiGenerator;
use Botnetdobbs\Luminous\Generator\TagRegistry;
use Botnetdobbs\Luminous\LuminousServiceProvider;
use Orchestra\Testbench\TestCase;

abstract class LuminousTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [LuminousServiceProvider::class];
    }

    /**
     * Build a generator with fresh registries and the current luminous config.
     * Prefer this over the app singleton so tests do not share registry state.
     */
    protected function makeGenerator(): OpenApiGenerator
    {
        return $this->app->make(GeneratorFactory::class)->make(
            config: config('luminous'),
            registry: new ComponentsRegistry,
            tagRegistry: new TagRegistry,
        );
    }
}
