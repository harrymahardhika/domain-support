<?php

declare(strict_types=1);

namespace HarryM\DomainSupport\Facades;

use Illuminate\Support\Facades\Facade;

class DomainSupport extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'domain-support';
    }
}
