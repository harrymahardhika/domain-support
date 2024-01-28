<?php

declare(strict_types=1);

namespace HarryM\DomainSupport;

use Illuminate\Support\ServiceProvider;

class DomainSupportServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot(): void
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'harrym');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'harrym');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/domain-support.php', 'domain-support');

        // Register the service the package provides.
        $this->app->singleton('domain-support', function ($app) {
            return new DomainSupport;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int,string>
     */
    public function provides(): array
    {
        return ['domain-support'];
    }

    /**
     * Console-specific booting.
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/domain-support.php' => config_path('domain-support.php'),
        ], 'domain-support.config');

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/harrym'),
        ], 'domain-support.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/harrym'),
        ], 'domain-support.assets');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/harrym'),
        ], 'domain-support.lang');*/

        // Registering package commands.
        // $this->commands([]);
    }
}
