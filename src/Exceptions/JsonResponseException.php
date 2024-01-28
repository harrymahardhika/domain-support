<?php

declare(strict_types=1);

namespace HarryM\DomainSupport\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class JsonResponseException extends Exception
{
    public function render(): JsonResponse
    {
        $code = (array_key_exists($this->getCode(), Response::$statusTexts)) ? $this->getCode() : 500;

        return response()->json(['message' => $this->getMessage()], $code);
    }
}
