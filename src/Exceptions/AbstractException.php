<?php

declare(strict_types=1);

namespace HarryM\DomainSupport\Exceptions;

use Exception;
use Throwable;

abstract class AbstractException extends Exception
{
    public function __construct(string $message = '', int $code = 400, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
