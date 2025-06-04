<?php

declare(strict_types=1);

namespace HarryM\DomainSupport\Enums;

use Illuminate\Support\Collection;

trait EnumTrait
{
    public static function fromName(string $name): mixed
    {
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
        return __($this->value);
    }
}
