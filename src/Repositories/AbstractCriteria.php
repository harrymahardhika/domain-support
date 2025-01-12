<?php

declare(strict_types=1);

namespace HarryM\DomainSupport\Repositories;

use Spatie\LaravelData\Data;

abstract class AbstractCriteria extends Data implements CriteriaInterface
{
    public function __construct(
        public ?string $search = null,
        public ?string $sort_column = null,
        public ?string $sort_order = null,
        public ?int $per_page = null,
    ) {}
}
