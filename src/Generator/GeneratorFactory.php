<?php

namespace Botnetdobbs\Luminous\Generator;

use Botnetdobbs\Luminous\Extractors\ControllerExtractor;
use Botnetdobbs\Luminous\Extractors\EnumExtractor;
use Botnetdobbs\Luminous\Extractors\RequestExtractor;
use Botnetdobbs\Luminous\Extractors\ResourceExtractor;
use Botnetdobbs\Luminous\Extractors\ResponseBuilder;
use Botnetdobbs\Luminous\Extractors\RouteExtractor;
use Botnetdobbs\Luminous\Extractors\RulesSchemaBuilder;
use Botnetdobbs\Luminous\Support\TypeMapper;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;

class GeneratorFactory
{
    public function __construct(private readonly Application $app) {}

    /**
     * Build an OpenApiGenerator graph.
     *
     * Pass custom registries when tests need isolation from the app singletons.
     * Pass a custom router when the default app router should not be used.
     */
    public function make(
        ?array $config = null,
        ?ComponentsRegistry $registry = null,
        ?TagRegistry $tagRegistry = null,
        ?Router $router = null,
    ): OpenApiGenerator {
        $config ??= $this->app['config']['luminous'];
        $registry ??= $this->app->make(ComponentsRegistry::class);
        $tagRegistry ??= $this->app->make(TagRegistry::class);
        $router ??= $this->app['router'];

        $enumExtractor = new EnumExtractor;
        $typeMapper = new TypeMapper($enumExtractor);
        $rulesBuilder = new RulesSchemaBuilder($typeMapper, $registry, $enumExtractor);
        $requestExtractor = new RequestExtractor($typeMapper, $registry, $enumExtractor, $rulesBuilder);
        $resourceExtractor = new ResourceExtractor($typeMapper, $registry, $enumExtractor);
        $responseBuilder = new ResponseBuilder($resourceExtractor, $config);

        return new OpenApiGenerator(
            config: $config,
            routeExtractor: new RouteExtractor($config, $router),
            controllerExtractor: new ControllerExtractor(
                requestExtractor: $requestExtractor,
                tagRegistry: $tagRegistry,
                responseBuilder: $responseBuilder,
                config: $config,
            ),
            registry: $registry,
            tagRegistry: $tagRegistry,
        );
    }
}
