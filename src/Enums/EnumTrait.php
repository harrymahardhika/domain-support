<?php

declare(strict_types=1);

namespace HarryM\DomainSupport\Enums;

use Illuminate\Support\Collection;

trait EnumTrait
{
    public static function fromName(string $name): mixed
    {
        if (! defined('static::'.$name)) {
            return null;
        }

        return constant('static::'.$name);
    }

    public static function list(): Collection
    {
        return collect(self::cases())->map(static fn ($case): array => [
            'name' => $case->name,
            'value' => $case->value,
            'label' => $case->translated(),
        ]);
    }

    public function translated(): string
    {
        $namespace = static::translationNamespace();

        $key = null === $namespace ? $this->value : "{$namespace}.{$this->value}";

        return __($key);
    }

    /**
     * The translation namespace prefixed to translated() lookups. Override
     * on the enum to use a namespace other than the package-wide default
     * from config('domain-support.translation_namespace').
     */
    protected static function translationNamespace(): ?string
    {
        /** @var string|null $default */
        $default = function_exists('config') ? config('domain-support.translation_namespace') : null;

        return $default;
    }
}
