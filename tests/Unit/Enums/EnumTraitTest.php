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

// Enum overriding the translation namespace
enum NamespacedTestEnum: string
{
    use EnumTrait;

    case ACTIVE = 'active';

    protected static function translationNamespace(): ?string
    {
        return 'app';
    }
}

describe('EnumTrait', function (): void {
    it('returns the case when fromName is called with a valid name', function (): void {
        expect(TestEnum::fromName('ACTIVE'))->toBe(TestEnum::ACTIVE);
    });

    it('returns null when fromName is called with an invalid name instead of crashing', function (): void {
        expect(TestEnum::fromName('NON_EXISTENT'))->toBeNull();
    });
});

describe('EnumTrait::translated()', function (): void {
    it('resolves a bare translation key by default', function (): void {
        config(['domain-support.translation_namespace' => null]);

        expect(TestEnum::ACTIVE->translated())->toBe('active');
    });

    it('resolves a namespaced translation key from config', function (): void {
        config(['domain-support.translation_namespace' => 'app']);

        // No matching lang file, so __() returns the key unchanged -
        // proving the namespaced key was actually requested.
        expect(TestEnum::ACTIVE->translated())->toBe('app.active');
    });

    it('lets an enum override the translation namespace regardless of config', function (): void {
        config(['domain-support.translation_namespace' => null]);

        expect(NamespacedTestEnum::ACTIVE->translated())->toBe('app.active');
    });
});
