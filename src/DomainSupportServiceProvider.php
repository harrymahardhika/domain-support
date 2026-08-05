<?php

declare(strict_types=1);

namespace HarryM\DomainSupport;

use HarryM\DomainSupport\Console\Commands\CreateDomain;
use HarryM\DomainSupport\Console\Commands\ListDomains;
use HarryM\DomainSupport\Console\Commands\MakeAction;
use HarryM\DomainSupport\Console\Commands\MakeConstant;
use HarryM\DomainSupport\Console\Commands\MakeController;
use HarryM\DomainSupport\Console\Commands\MakeCriteria;
use HarryM\DomainSupport\Console\Commands\MakeCrud;
use HarryM\DomainSupport\Console\Commands\MakeEnum;
use HarryM\DomainSupport\Console\Commands\MakeEvent;
use HarryM\DomainSupport\Console\Commands\MakeException;
use HarryM\DomainSupport\Console\Commands\MakeModel;
use HarryM\DomainSupport\Console\Commands\MakeRepository;
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

        $this->publishes([
            __DIR__.'/../stubs' => base_path('stubs/domain-support'),
        ], 'domain-support.stubs');

        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateDomain::class,
                ListDomains::class,
                MakeAction::class,
                MakeConstant::class,
                MakeController::class,
                MakeCriteria::class,
                MakeCrud::class,
                MakeEnum::class,
                MakeEvent::class,
                MakeException::class,
                MakeModel::class,
                MakeRepository::class,
            ]);
        }
    }
}
