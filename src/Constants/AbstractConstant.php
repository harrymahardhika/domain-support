<?php

declare(strict_types=1);

namespace HarryM\DomainSupport\Constants;

use Illuminate\Support\Collection;
use ReflectionClass;

abstract class AbstractConstant
{
    /**
     * @return Collection<string, mixed>
     */
    public static function get(): Collection
    {
        return collect(new ReflectionClass(static::class)->getConstants());
    }
}
