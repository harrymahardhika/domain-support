<?php

declare(strict_types=1);

use HarryM\DomainSupport\Models\AbstractModel;
use Illuminate\Support\Facades\Config;

// Dummy Model for testing AbstractModel default perPage
class TestDefaultModel extends AbstractModel
{
    protected static function newFactory(): mixed
    {
        return null;
    }
}

describe('AbstractModel', function (): void {
    it('uses a default perPage of 15 when config is missing', function (): void {
        Config::set('domain-support', []);

        $model = new TestDefaultModel();

        expect($model->getPerPage())->toBe(15);
    });

    it('uses the configured perPage when available', function (): void {
        Config::set('domain-support.per_page', 25);

        $model = new TestDefaultModel();

        expect($model->getPerPage())->toBe(25);
    });
});
