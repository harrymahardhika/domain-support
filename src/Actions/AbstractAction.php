<?php

declare(strict_types=1);

namespace HarryM\DomainSupport\Actions;

use Illuminate\Foundation\Bus\Dispatchable;

abstract class AbstractAction
{
    use Dispatchable;

    /**
     * @return mixed|void
     */
    abstract public function handle();
}
