<?php

declare(strict_types=1);

namespace HarryM\DomainSupport\Tests;

use HarryM\DomainSupport\DomainSupportServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    #[\Override]
    protected function getPackageProviders($app)
    {
        return [
            DomainSupportServiceProvider::class,
        ];
    }
}
