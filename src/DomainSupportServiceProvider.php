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
    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/domain-support.php', 'domain-support');

        $this->app->singleton('domain-support', fn (): DomainSupport => new DomainSupport);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int,string>
     */
    #[\Override]
    public function provides(): array
    {
        return ['domain-support'];
    }

    /**
     * Console-specific booting.
     */
    protected function bootForConsole(): void
    {
        $this->publishes([
            __DIR__.'/../config/domain-support.php' => config_path('domain-support.php'),
        ], 'domain-support.config');
    }
}
