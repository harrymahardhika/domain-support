<?php

declare(strict_types=1);

use HarryM\DomainSupport\Controllers\SendsJsonResponse;
use Illuminate\Http\JsonResponse;

class TestJsonResponseController
{
    use SendsJsonResponse;
}

describe('SendsJsonResponse trait', function (): void {
    it('returns a JsonResponse with the given content and status code', function (): void {
        $controller = new TestJsonResponseController();

        $response = $controller->sendJsonResponse(['message' => 'ok'], 201);

        expect($response)->toBeInstanceOf(JsonResponse::class);
        expect($response->getStatusCode())->toBe(201);
        expect($response->getData(true))->toBe(['message' => 'ok']);
    });

    it('defaults to a 200 status code', function (): void {
        $controller = new TestJsonResponseController();

        $response = $controller->sendJsonResponse(['message' => 'ok']);

        expect($response->getStatusCode())->toBe(200);
    });

    it('does not escape unicode characters in the response body', function (): void {
        $controller = new TestJsonResponseController();

        $response = $controller->sendJsonResponse(['name' => 'カナ漢字']);
        $content = $response->getContent();

        expect($content)->toContain('カナ漢字');
        expect($content)->not->toContain('\u');
    });
});
