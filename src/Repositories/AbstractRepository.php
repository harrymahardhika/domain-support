<?php

declare(strict_types=1);

namespace HarryM\DomainSupport\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class AbstractRepository
{
    public const SORT_ORDER_ASC = 'asc';
    public const SORT_ORDER_DESC = 'desc';

    protected string $model;

    /** @var Builder<Model> */
    protected Builder $query;

    protected string $sortColumn = 'created_at';

    protected string $sortOrder = self::SORT_ORDER_DESC;

    protected ?int $limit = null;

    protected ?int $perPage = null;

    /** @var array<string>|null */
    protected ?array $sortableColumns = [];

    /** @var array<string>|null */
    protected ?array $searchableColumns = [];

    /** @var array<string>|null */
    protected ?array $with = [];

    public function __construct(protected ?CriteriaInterface $criteria = null)
    {
        $this->makeQuery();
    }

    private function makeQuery(): void
    {
        /** @var Model $model */
        $model = new $this->model;

        $query = $model->query();

        $this->query = null === $this->with || [] === $this->with ? $query : $query->with($this->with);

        if ($this->criteria instanceof CriteriaInterface) {
            foreach ($this->criteria->toArray() as $key => $value) {
                $key = Str::camel($key);
                if ($value) {
                    $this->$key($value);
                }
            }
        }
    }

    public function search(string $keyword): static
    {
        if (null !== $this->searchableColumns && [] !== $this->searchableColumns) {
            $keyword = sprintf('%%%s%%', $keyword);

            /** @var array<string> $searchableColumns */
            $searchableColumns = $this->searchableColumns;

            $this->query = $this->query
                ->where(function (\Illuminate\Contracts\Database\Query\Builder $query) use ($searchableColumns, $keyword): void {
                    foreach ($searchableColumns as $column) {
                        $query->orWhere($column, 'ilike', $keyword);
                    }
                });
        }

        return $this;
    }

    public function sortColumn(string $column): static
    {
        if ($this->sortableColumns && in_array($column, $this->sortableColumns, true)) {
            $this->sortColumn = $column;
        }

        return $this;
    }

    public function sortOrder(string $order): static
    {
        $this->sortOrder = $order;

        return $this;
    }

    public function perPage(int $perPage): static
    {
        $this->perPage = $perPage;

        return $this;
    }

    public function limit(int $limit): void
    {
        $this->limit = $limit;
    }

    protected function orderBy(): static
    {
        $this->query->orderBy($this->sortColumn, $this->sortOrder);

        return $this;
    }

    /**
     * @return Collection<int,Model>
     */
    public function get(): Collection
    {
        $this->orderBy();

        if ($this->limit) {
            $this->query->limit($this->limit);
        }

        return $this->query->get();
    }

    /**
     * @param  array<string,string>|null                         $appends
     * @return LengthAwarePaginator<Model>|Collection<int,Model>
     */
    public function paginate(?array $appends = null): LengthAwarePaginator|Collection
    {
        $appends ??= [];

        $this->orderBy();

        return $this->query->paginate($this->perPage)->appends($appends);
    }
}
