<?php

declare(strict_types=1);

use HarryM\DomainSupport\Exceptions\JsonResponseException;
use Illuminate\Http\JsonResponse;

describe('JsonResponseException', function (): void {
    it('renders with the correct status code when valid', function (): void {
        $exception = new JsonResponseException('Forbidden', 403);
        $response = $exception->render();

        expect($response)->toBeInstanceOf(JsonResponse::class);
        expect($response->getStatusCode())->toBe(403);
        expect($response->getData(true))->toBe(['message' => 'Forbidden']);
    });

    it('defaults to 500 when the code is 0', function (): void {
        $exception = new JsonResponseException('Something went wrong', 0);
        $response = $exception->render();

        expect($response->getStatusCode())->toBe(500);
    });

    it('defaults to 500 when the code is an invalid HTTP status code', function (): void {
        $exception = new JsonResponseException('Invalid code', 999);
        $response = $exception->render();

        expect($response->getStatusCode())->toBe(500);
    });
});
