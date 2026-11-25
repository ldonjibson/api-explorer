<?php

namespace Ldonjibson\ApiExplorer;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Ldonjibson\ApiExplorer\Support\EndpointRegistry;

class ApiExplorerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/api-explorer.php', 'api-explorer');

        $this->app->singleton(EndpointRegistry::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'api-explorer');

        Route::prefix(config('api-explorer.path', 'api-explorer'))
            ->name('api-explorer.')
            ->group(function () {
                require __DIR__.'/../routes/api-explorer.php';
            });

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/api-explorer.php' => config_path('api-explorer.php'),
            ], ['api-explorer-config', 'api-explorer']);
        }
    }
}
