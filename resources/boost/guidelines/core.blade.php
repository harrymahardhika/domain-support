## HarryM DomainSupport

`harrym/domain-support` provides abstract base classes for domain-driven architecture in Laravel apps: actions, repositories, models, controllers, events, exceptions, constants, and enums. The package ships **no application code** — only abstractions to extend and a service provider that registers them and the `domain:*` Artisan commands below.

### Directory convention

Domain code lives under `app/Domains/{Domain}/{Type}`, e.g. `app/Domains/Blog/Actions`, `app/Domains/Blog/Repositories`. The recognized types are `Actions`, `Constants`, `Controllers`, `DTO`, `Enums`, `Events`, `Exceptions`, `Models`, `Repositories`, `Requests`. Namespaces mirror the path: `App\Domains\Blog\Actions`.

### Scaffolding — prefer these commands over hand-writing boilerplate

- `domain:create-domain {domain}` — creates the empty directory skeleton for a new domain.
- `domain:make-model {domain} {name}` — extends `AbstractModel`.
- `domain:make-repository {domain} {name} [--model=]` — extends `AbstractRepository`; defaults `$model` to a same-domain model named after the repository.
- `domain:make-criteria {domain} {name}` — extends `AbstractCriteria` (a `spatie/laravel-data` `Data` object).
- `domain:make-controller {domain} {name} [--web]` — extends `AbstractAPIController`, or `AbstractWebController` with `--web`.
- `domain:make-action {domain} {name} [--async]` — extends `AbstractAction`, or `AbstractAsyncAction` (queued) with `--async`.
- `domain:make-exception {domain} {name} [--code=400]` — extends `AbstractException`.
- `domain:make-event {domain} {name}` — extends `AbstractEvent`.
- `domain:make-constant {domain} {name}` — extends `AbstractConstant`.
- `domain:make-enum {domain} {name}` — backed enum using `EnumTrait`.
- `domain:make-crud {domain} {name}` — runs the model/repository/criteria/controller/action generators together for one resource.
- `domain:list` — lists existing domains and a summary of their contents.

All `make-*` commands accept `--force` to overwrite an existing file.

@verbatim
<code-snippet name="Scaffold a domain and a CRUD resource" lang="bash">
php artisan domain:create-domain Blog
php artisan domain:make-crud Blog Post
</code-snippet>
@endverbatim

### Repository + Criteria: how filtering is wired

`AbstractRepository` is constructed with an optional `CriteriaInterface`. On construction, the criteria's `toArray()` is walked; each key is camelCased and, if a method of that name exists on the repository, it's called with the value. **To add a new filterable field, add a public method to the repository subclass named after the camelCased criteria field** — the criteria object never needs to know about the repository's internals.

@verbatim
<code-snippet name="Repository and criteria pair" lang="php">
final class PostCriteria extends AbstractCriteria
{
    // search, sort_column, sort_order, per_page, and limit are inherited.
    // Add domain-specific fields here, e.g. public ?string $status = null;
}

final class PostRepository extends AbstractRepository
{
    protected string $model = Post::class;

    protected array $sortableColumns = ['title', 'created_at'];

    protected array $searchableColumns = ['title', 'body'];

    protected array $with = [];

    // Matches the camelCased `status` field on PostCriteria.
    public function status(string $status): static
    {
        $this->query->where('status', $status);

        return $this;
    }
}

$posts = new PostRepository(PostCriteria::from($request))->get();
</code-snippet>
@endverbatim

`search()` uses Laravel Scout when the model uses `Laravel\Scout\Searchable` and `withScout()` is enabled, falling back to a database `LIKE`/`ILIKE` search over `$searchableColumns` (branching on the `pgsql` driver).

### Actions, controllers, exceptions

- `AbstractAction`/`AbstractAsyncAction` implement `handle()` and are `Dispatchable` — call them with `PublishPost::dispatch(...)`, not by instantiating and calling `handle()` directly, unless writing a unit test against the action itself.
- `AbstractAPIController` includes `SendsJsonResponse` — use `$this->sendJsonResponse($data, $code)` for standardized JSON responses instead of `response()->json()`.
- Domain errors should extend `AbstractException` (defaults to HTTP 400); prefer `throw_if()`/`throw_unless()` for inline validation.

### Configuration

`config/domain-support.php` (published via `--tag="domain-support.config"`) exposes `per_page`, used by `AbstractModel` to set the Eloquent pagination count. Generator stubs can be overridden per-application by publishing `--tag="domain-support.stubs"` into `stubs/domain-support/`.
