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

        $this->app->singleton('domain-support', fn ($app): DomainSupport => new DomainSupport);
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
    }
}
