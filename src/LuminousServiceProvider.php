<?php

namespace Botnetdobbs\Luminous;

use Botnetdobbs\Luminous\Commands\ExportCommand;
use Botnetdobbs\Luminous\Commands\GenerateCommand;
use Botnetdobbs\Luminous\Contracts\OpenApiGeneratorContract;
use Botnetdobbs\Luminous\Generator\ComponentsRegistry;
use Botnetdobbs\Luminous\Generator\GeneratorFactory;
use Botnetdobbs\Luminous\Generator\OpenApiGenerator;
use Botnetdobbs\Luminous\Generator\TagRegistry;
use Botnetdobbs\Luminous\Http\Controllers\LuminousController;
use Botnetdobbs\Luminous\Support\CacheManager;
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
        $this->app->singleton(GeneratorFactory::class);

        $this->app->singleton(OpenApiGenerator::class, fn ($app) => $app->make(GeneratorFactory::class)->make());

        $this->app->singleton(OpenApiGeneratorContract::class, fn ($app) => $app->make(OpenApiGenerator::class));

        $this->app->alias(OpenApiGenerator::class, 'luminous');

        $this->app->singleton(CacheManager::class, fn ($app) => new CacheManager($app['config']['luminous']));
        $this->app->singleton(YamlExporter::class);
    }

    public function boot(): void
    {
        config()->set('luminous.ui.drivers', require __DIR__.'/config/luminous-ui.php');

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
