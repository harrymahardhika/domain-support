<?php

declare(strict_types=1);

namespace HarryM\DomainSupport\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class AbstractEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;
}
