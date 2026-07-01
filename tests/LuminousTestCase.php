<?php

namespace Botnetdobbs\Luminous\Tests;

use Botnetdobbs\Luminous\Extractors\ControllerExtractor;
use Botnetdobbs\Luminous\Extractors\EnumExtractor;
use Botnetdobbs\Luminous\Extractors\RequestExtractor;
use Botnetdobbs\Luminous\Extractors\ResourceExtractor;
use Botnetdobbs\Luminous\Extractors\ResponseBuilder;
use Botnetdobbs\Luminous\Extractors\RouteExtractor;
use Botnetdobbs\Luminous\Extractors\RulesSchemaBuilder;
use Botnetdobbs\Luminous\Generator\ComponentsRegistry;
use Botnetdobbs\Luminous\Generator\OpenApiGenerator;
use Botnetdobbs\Luminous\Generator\TagRegistry;
use Botnetdobbs\Luminous\LuminousServiceProvider;
use Botnetdobbs\Luminous\Support\TypeMapper;
use Orchestra\Testbench\TestCase;

abstract class LuminousTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [LuminousServiceProvider::class];
    }

    protected function makeGenerator(): OpenApiGenerator
    {
        $config = config('luminous');
        $registry = new ComponentsRegistry;
        $enumEx = new EnumExtractor;
        $typeMapper = new TypeMapper($enumEx);
        $rulesBuilder = new RulesSchemaBuilder($typeMapper, $registry, $enumEx);
        $requestEx = new RequestExtractor($typeMapper, $registry, $enumEx, $rulesBuilder);
        $resourceEx = new ResourceExtractor($typeMapper, $registry, $enumEx);
        $tagRegistry = new TagRegistry;
        $responseBuilder = new ResponseBuilder($resourceEx, $config);
        $controllerEx = new ControllerExtractor($requestEx, $tagRegistry, $responseBuilder, $config);
        $routeEx = new RouteExtractor($config, $this->app['router']);

        return new OpenApiGenerator(
            config: $config,
            routeExtractor: $routeEx,
            controllerExtractor: $controllerEx,
            registry: $registry,
            tagRegistry: $tagRegistry,
        );
    }
}
