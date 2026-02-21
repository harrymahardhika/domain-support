<?php

declare(strict_types=1);

use HarryM\DomainSupport\Enums\EnumTrait;

// Dummy Enum for testing EnumTrait
enum TestEnum: string
{
    use EnumTrait;

    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}

describe('EnumTrait', function (): void {
    it('returns the case when fromName is called with a valid name', function (): void {
        expect(TestEnum::fromName('ACTIVE'))->toBe(TestEnum::ACTIVE);
    });

    it('returns null when fromName is called with an invalid name instead of crashing', function (): void {
        expect(TestEnum::fromName('NON_EXISTENT'))->toBeNull();
    });
});
