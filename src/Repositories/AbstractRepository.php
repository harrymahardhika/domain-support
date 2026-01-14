<?php

declare(strict_types=1);

namespace HarryM\DomainSupport\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class AbstractRepository
{
    public const string SORT_ORDER_ASC = 'asc';

    public const string SORT_ORDER_DESC = 'desc';

    protected string $model;

    /** @var Builder<Model> */
    protected Builder $query;

    protected string $sortColumn = 'created_at';

    protected string $sortOrder = self::SORT_ORDER_DESC;

    protected ?int $limit = null;

    protected ?int $perPage = null;

    protected bool $useScout = false;

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

    /**
     * Search for records using Laravel Scout if available, otherwise fallback to database search.
     */
    public function search(string $keyword): static
    {
        // Skip empty searches
        if ('' === mb_trim($keyword)) {
            return $this;
        }

        // Check if Scout should be used
        if ($this->shouldUseScout()) {
            return $this->searchWithScout($keyword);
        }

        return $this->searchWithDatabase($keyword);
    }

    /**
     * Set the sort column from allowed sortable columns.
     */
    public function sortColumn(string $column): static
    {
        if ($this->sortableColumns && in_array($column, $this->sortableColumns, true)) {
            $this->sortColumn = $column;
        }

        return $this;
    }

    /**
     * Set the sort order (asc or desc).
     */
    public function sortOrder(string $order): static
    {
        $normalizedOrder = mb_strtolower(mb_trim($order));

        if (in_array($normalizedOrder, [self::SORT_ORDER_ASC, self::SORT_ORDER_DESC], true)) {
            $this->sortOrder = $normalizedOrder;
        }

        return $this;
    }

    /**
     * Set the number of items per page for pagination.
     */
    public function perPage(int $perPage): static
    {
        $this->perPage = $perPage;

        return $this;
    }

    /**
     * Set the maximum number of results to return.
     */
    public function limit(int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Enable Laravel Scout for search operations.
     */
    public function withScout(bool $enabled = true): static
    {
        $this->useScout = $enabled;

        return $this;
    }

    /**
     * Get all results based on current query criteria.
     *
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
     * Get paginated results based on current query criteria.
     *
     * @param  array<string,string>|null        $appends
     * @return LengthAwarePaginator<int, Model>
     */
    public function paginate(?array $appends = null): LengthAwarePaginator
    {
        $appends ??= [];

        $this->orderBy();

        return $this->query->paginate($this->perPage)->appends($appends);
    }

    /**
     * Reset the query builder to start fresh.
     */
    public function reset(): static
    {
        $this->sortColumn = 'created_at';
        $this->sortOrder = self::SORT_ORDER_DESC;
        $this->limit = null;
        $this->perPage = null;
        $this->useScout = false;

        $this->makeQuery();

        return $this;
    }

    /**
     * Determine if Laravel Scout should be used for search.
     */
    protected function shouldUseScout(): bool
    {
        if (! $this->useScout) {
            return false;
        }

        if (! trait_exists('Laravel\\Scout\\Searchable')) {
            return false;
        }

        $modelTraits = class_uses_recursive($this->model);

        return in_array('Laravel\\Scout\\Searchable', $modelTraits, true);
    }

    /**
     * Search using Laravel Scout.
     */
    protected function searchWithScout(string $keyword): static
    {
        /** @var Model $model */
        $model = new $this->model;

        /** @phpstan-ignore staticMethod.notFound */
        $scoutBuilder = $model::search($keyword);

        // Get IDs from Scout
        $ids = $scoutBuilder->keys();

        if ($ids->isEmpty()) {
            // No results, add impossible where clause
            $this->query->whereRaw('1 = 0');
        } else {
            // Filter query by Scout results
            $this->query->whereIn($model->getKeyName(), $ids->toArray());
        }

        return $this;
    }

    /**
     * Search using database queries with LIKE/ILIKE.
     */
    protected function searchWithDatabase(string $keyword): static
    {
        if (null === $this->searchableColumns || [] === $this->searchableColumns) {
            return $this;
        }

        $keyword = sprintf('%%%s%%', $keyword);

        /** @var Connection $connection */
        $connection = $this->query->getConnection();
        $databaseDriver = $connection->getDriverName();

        /** @var array<string> $searchableColumns */
        $searchableColumns = $this->searchableColumns;

        if ('pgsql' === $databaseDriver) {
            $this->query = $this->query->where(function (Builder $query) use ($searchableColumns, $keyword): void {
                foreach ($searchableColumns as $column) {
                    $query->orWhere($column, 'ilike', $keyword);
                }
            });
        } else {
            $this->query = $this->query->where(function (Builder $query) use ($searchableColumns, $keyword): void {
                foreach ($searchableColumns as $column) {
                    $query->orWhereRaw(sprintf('LOWER(%s) LIKE ?', $column), [mb_strtolower($keyword)]);
                }
            });
        }

        return $this;
    }

    /**
     * Apply ORDER BY clause to the query.
     */
    protected function orderBy(): static
    {
        $this->query->orderBy($this->sortColumn, $this->sortOrder);

        return $this;
    }

    /**
     * Initialize the query builder with model and criteria.
     */
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
}
