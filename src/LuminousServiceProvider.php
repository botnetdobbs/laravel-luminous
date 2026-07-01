<?php

namespace Botnetdobbs\Luminous;

use Botnetdobbs\Luminous\Commands\ExportCommand;
use Botnetdobbs\Luminous\Commands\GenerateCommand;
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
use Botnetdobbs\Luminous\Http\Controllers\LuminousController;
use Botnetdobbs\Luminous\Support\CacheManager;
use Botnetdobbs\Luminous\Support\TypeMapper;
use Botnetdobbs\Luminous\Support\YamlExporter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LuminousServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/luminous.php', 'luminous');

        $this->app->singleton(ComponentsRegistry::class);
        $this->app->singleton(TagRegistry::class);

        $this->app->singleton(OpenApiGenerator::class, function ($app) {
            $config = $app['config']['luminous'];
            $registry = $app->make(ComponentsRegistry::class);
            $tagRegistry = $app->make(TagRegistry::class);
            $routeExtractor = new RouteExtractor($config, $app['router']);

            $enumExtractor = new EnumExtractor;
            $typeMapper = new TypeMapper($enumExtractor);
            $rulesBuilder = new RulesSchemaBuilder($typeMapper, $registry, $enumExtractor);
            $requestExtractor = new RequestExtractor($typeMapper, $registry, $enumExtractor, $rulesBuilder);
            $resourceExtractor = new ResourceExtractor($typeMapper, $registry, $enumExtractor);
            $responseBuilder = new ResponseBuilder($resourceExtractor, $config);

            return new OpenApiGenerator(
                config: $config,
                routeExtractor: $routeExtractor,
                controllerExtractor: new ControllerExtractor(
                    requestExtractor: $requestExtractor,
                    tagRegistry: $tagRegistry,
                    responseBuilder: $responseBuilder,
                    config: $config,
                ),
                registry: $registry,
                tagRegistry: $tagRegistry,
            );
        });

        $this->app->alias(OpenApiGenerator::class, 'luminous');

        $this->app->singleton(CacheManager::class, fn ($app) => new CacheManager($app['config']['luminous']));
        $this->app->singleton(YamlExporter::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/luminous.php' => config_path('luminous.php'),
        ], 'luminous-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/luminous'),
        ], 'luminous-views');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'luminous');

        if ($this->app['config']['luminous.enabled']) {
            $this->loadRoutes();

            if (empty($this->app['config']['luminous.middleware'])
                && ! $this->app->environment('local', 'testing')) {
                logger()->warning(
                    'Luminous: API docs are publicly accessible with no middleware. '.
                    'Set LUMINOUS_MIDDLEWARE=auth or LUMINOUS_ENABLED=false in your .env.'
                );
            }
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateCommand::class,
                ExportCommand::class,
            ]);
        }
    }

    private function loadRoutes(): void
    {
        $middleware = $this->app['config']['luminous.middleware'] ?? [];
        $prefix = $this->app['config']['luminous.path'] ?? 'docs';

        Route::middleware($middleware)
            ->prefix($prefix)
            ->group(function () {
                Route::get('/', [LuminousController::class, 'ui'])->name('luminous.ui');
                Route::get('/openapi.json', [LuminousController::class, 'json'])->name('luminous.json');
                Route::get('/openapi.yaml', [LuminousController::class, 'yaml'])->name('luminous.yaml');
            });
    }
}
