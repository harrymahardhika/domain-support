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
        $code = $this->getCode();
        if ($code < 100 || $code >= 600 || ! array_key_exists($code, Response::$statusTexts)) {
            $code = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        return new JsonResponse(['message' => $this->getMessage()], $code);
    }
}
