<?php

declare(strict_types=1);

namespace HarryM\DomainSupport\Repositories;

use HarryM\DomainSupport\Exceptions\UnmappedCriteriaException;
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

    public const int SCOUT_SEARCH_RESULT_LIMIT = 10000;

    protected string $model;

    /** @var Builder<Model> */
    protected Builder $query;

    protected string $sortColumn = 'created_at';

    protected string $sortOrder = self::SORT_ORDER_DESC;

    protected ?int $limit = null;

    protected ?int $perPage = null;

    protected bool $useScout = false;

    protected bool $preserveScoutOrder = false;

    protected bool $sortExplicitlySet = false;

    protected ?string $scoutOrderClause = null;

    /** @var array<int,int|string> */
    protected array $scoutOrderBindings = [];

    /** @var array<string>|null */
    protected ?array $sortableColumns = [];

    /** @var array<string>|null */
    protected ?array $searchableColumns = [];

    /** @var array<string, array<string>> */
    protected array $searchableRelations = [];

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
            $this->sortExplicitlySet = true;
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
            $this->sortExplicitlySet = true;
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

        $perPage = $this->perPage;

        if ($this->limit && (null === $perPage || $this->limit < $perPage)) {
            $perPage = $this->limit;
        }

        return $this->query->paginate($perPage)->appends($appends);
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
        $this->preserveScoutOrder = false;
        $this->sortExplicitlySet = false;
        $this->scoutOrderClause = null;
        $this->scoutOrderBindings = [];

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
        $scoutBuilder = $model::search($keyword)->take(self::SCOUT_SEARCH_RESULT_LIMIT);

        // Get IDs from Scout, already ranked by relevance/score
        /** @var array<int,int|string> $ids */
        $ids = $scoutBuilder->keys()->toArray();

        if ([] === $ids) {
            // No results, add impossible where clause
            $this->query->whereRaw('1 = 0');

            return $this;
        }

        // Filter query by Scout results
        $keyName = $model->getKeyName();
        $this->query->whereIn($keyName, $ids);

        // Preserve Meilisearch's relevance ordering instead of falling back
        // to the default ORDER BY; applied later in orderBy() so it doesn't
        // get stacked alongside (rather than replaced by) the default sort.
        $this->scoutOrderClause = $this->buildScoutOrderClause($keyName, $ids);
        $this->scoutOrderBindings = $ids;
        $this->preserveScoutOrder = true;

        return $this;
    }

    /**
     * Extension point for searching related models' columns alongside
     * $searchableColumns, without overriding searchWithDatabase() entirely.
     * Keyed by relation name to the list of columns on that relation to search.
     *
     * @return array<string, array<string>>
     */
    protected function searchableRelations(): array
    {
        return $this->searchableRelations;
    }

    /**
     * Search using database queries with LIKE/ILIKE.
     */
    protected function searchWithDatabase(string $keyword): static
    {
        /** @var array<string> $searchableColumns */
        $searchableColumns = $this->searchableColumns ?? [];
        $searchableRelations = $this->searchableRelations();

        if ([] === $searchableColumns && [] === $searchableRelations) {
            return $this;
        }

        /** @var Connection $connection */
        $connection = $this->query->getConnection();
        $databaseDriver = $connection->getDriverName();

        $this->query = $this->query->where(function (Builder $query) use ($searchableColumns, $searchableRelations, $keyword, $databaseDriver): void {
            if ([] !== $searchableColumns) {
                $this->applyColumnSearch($query, $searchableColumns, $databaseDriver, $keyword);
            }

            foreach ($searchableRelations as $relation => $columns) {
                $query->orWhereHas($relation, function (Builder $relationQuery) use ($columns, $databaseDriver, $keyword): void {
                    $relationQuery->where(function (Builder $relationQuery) use ($columns, $databaseDriver, $keyword): void {
                        $this->applyColumnSearch($relationQuery, $columns, $databaseDriver, $keyword);
                    });
                });
            }
        });

        return $this;
    }

    /**
     * Apply ORDER BY clause to the query.
     */
    protected function orderBy(): static
    {
        // Respect Meilisearch's relevance ordering unless the caller
        // explicitly asked for a specific sort column/order.
        if ($this->preserveScoutOrder && ! $this->sortExplicitlySet) {
            if (null !== $this->scoutOrderClause) {
                /** @var literal-string $scoutOrderClause */
                $scoutOrderClause = $this->scoutOrderClause;
                $this->query->orderByRaw($scoutOrderClause, $this->scoutOrderBindings);
            }

            return $this;
        }

        /** @var 'asc'|'desc' $sortOrder */
        $sortOrder = $this->sortOrder;
        $this->query->orderBy($this->sortColumn, $sortOrder);

        return $this;
    }

    /**
     * Apply an OR'd LIKE/ILIKE condition across the given columns on the
     * given query builder, branching on database driver for case-insensitivity.
     *
     * @param Builder<Model> $query
     * @param array<string>  $columns
     */
    private function applyColumnSearch(Builder $query, array $columns, string $databaseDriver, string $keyword): void
    {
        if ('pgsql' === $databaseDriver) {
            $likeKeyword = sprintf('%%%s%%', $keyword);

            foreach ($columns as $column) {
                $query->orWhere($column, 'ilike', $likeKeyword);
            }

            return;
        }

        // For other drivers like SQLite/MySQL, use LOWER() for consistent case-insensitivity
        $likeKeyword = sprintf('%%%s%%', mb_strtolower($keyword));

        foreach ($columns as $column) {
            $sql = sprintf('LOWER(%s) LIKE ?', $column);

            /** @var literal-string $sql */
            $query->orWhereRaw($sql, [$likeKeyword]);
        }
    }

    /**
     * Build a portable CASE expression that ranks rows by their position in
     * the Scout results, so the database preserves the search engine's order.
     *
     * @param array<int,int|string> $ids
     */
    private function buildScoutOrderClause(string $keyName, array $ids): string
    {
        $cases = implode(' ', array_map(
            static fn (int $position): string => sprintf('WHEN %s = ? THEN %d', $keyName, $position),
            array_keys($ids),
        ));

        return sprintf('CASE %s ELSE %d END', $cases, count($ids));
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

                if (null === $value) {
                    continue;
                }

                if (! method_exists($this, $key)) {
                    if (function_exists('app') && app()->environment('local', 'testing')) {
                        throw new UnmappedCriteriaException($key, static::class);
                    }

                    continue;
                }

                $this->$key($value);
            }
        }
    }
}
