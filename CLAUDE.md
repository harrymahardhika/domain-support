# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

`harrym/domain-support` is a Laravel package (PHP 8.4+) providing abstract base classes for domain-driven architecture: actions, repositories, models, controllers, events, exceptions, and constants. Applications extend these base classes; the package itself has no application code, only the reusable abstractions and a service provider that registers them.

Key dependency: `spatie/laravel-data` (used by `AbstractCriteria`, which extends `Data`).

## Commands

```bash
composer install                                        # install dependencies
./vendor/bin/pest                                        # run all tests
./vendor/bin/pest tests/Unit/Actions/ActionTest.php       # run a single test file
./vendor/bin/pest --filter "test name"                    # run a specific test by name
./vendor/bin/pest --coverage                              # run tests with coverage
./vendor/bin/phpstan analyse --memory-limit=1G            # static analysis (level 9, via Larastan)
./vendor/bin/pint                                         # auto-format code (PSR-12 + Laravel preset)
./vendor/bin/rector process                                # apply automated refactoring rules
```

Composer script aliases exist for the formatter and refactor tool: `composer pint`, `composer rector`, `composer test`.

CI (`.github/workflows/test.yml`) runs `vendor/bin/pest` against PHP 8.4 with a real Postgres 16 service — the repository/search tests rely on Postgres-specific behavior (e.g. `ilike`), so prefer testing against Postgres when in doubt about search behavior.

## Architecture

### Core abstractions (`src/`)

- **`Actions/AbstractAction`** — business logic entry point; uses `Dispatchable`, implement `handle()`.
- **`Actions/AbstractAsyncAction`** — queued variant of the above; implements `ShouldQueue` and combines `Dispatchable`, `InteractsWithQueue`, `Queueable`, `SerializesModels`.
- **`Repositories/AbstractRepository`** + **`CriteriaInterface`** + **`AbstractCriteria`** — the fluent query layer. A repository is constructed with an optional `CriteriaInterface`; `AbstractCriteria` (a spatie `Data` object with `search`, `sort_column`, `sort_order`, `per_page`, `limit`) is converted `toArray()`, each key camelCased, and matched against a repository method of the same name (e.g. `sort_column` → `sortColumn()`). This is how criteria objects drive query building without the repository knowing about the criteria's shape — new filterable fields are added by adding a matching public method to the repository subclass.
  - Search falls back between Laravel Scout (`withScout()`, requires the model to use `Laravel\Scout\Searchable`) and a database `LIKE`/`ILIKE` search over `searchableColumns`, branching on DB driver (`pgsql` uses `ilike`; others use `LOWER(...) LIKE`). The database fallback also supports `$searchableRelations` (or overriding the `searchableRelations()` method) — an array keyed by relation name to the columns on that relation to search, added as `orWhereHas()` clauses alongside `searchableColumns` without needing to override `searchWithDatabase()`.
  - Subclasses configure behavior via protected properties: `$model` (FQCN string), `$sortableColumns`, `$searchableColumns`, `$searchableRelations`, `$with` (eager-load relations).
- **`Models/AbstractModel`** — base Eloquent model with `SoftDeletes` + `HasFactory`, pagination count configured from the `domain-support.php` published config.
- **`Controllers/AbstractAPIController`** (uses `SendsJsonResponse` trait for standardized JSON responses) and **`Controllers/AbstractWebController`**.
- **`Exceptions/AbstractException`** — base for domain exceptions, defaults to HTTP 400.
- **`Events/AbstractEvent`**, **`Constants/AbstractConstant`**, **`Enums/EnumTrait`** — supporting utilities.
- **`Console/Commands/CreateDomain`** (`domain:create-domain {domain}`) — scaffolds a domain's directory skeleton (`Actions`, `Constants`, `Controllers`, `DTO`, `Enums`, `Events`, `Exceptions`, `Models`, `Repositories`, `Requests`) under `app/Domains/{domain}` in the consuming application.
- **`Console/Commands/Make*`** (`MakeAction`, `MakeRepository`, `MakeCriteria`, `MakeModel`, `MakeController`, `MakeException`, `MakeEvent`, `MakeConstant`, `MakeEnum`) — one generator per abstract class, each rendering a `stubs/*.stub` file into `app/Domains/{domain}/{Type}/{Name}.php` with the namespace and class name substituted. Shared logic (path building, the `--force`/already-exists check, stub resolution) lives in the `Console\Commands\Concerns\GeneratesDomainFile` trait; stub content is looked up in an app-published `stubs/domain-support/` override before falling back to the package's own `stubs/`. `MakeCrud` chains model/repository/criteria/controller/action for a single resource; `ListDomains` (`domain:list`) reports existing domains and their contents. All are plain `Illuminate\Console\Command` classes, not Laravel's `GeneratorCommand`.
- **`DomainSupportServiceProvider`** — merges `config/domain-support.php`, registers the `domain-support` singleton/facade, registers all console commands, and publishes both the config (`domain-support.config` tag) and the stubs (`domain-support.stubs` tag).

### Laravel Boost integration (`resources/boost/`)

- `resources/boost/guidelines/core.blade.php` — auto-discovered by Laravel Boost's `ThirdPartyPackage::discover()` (no composer.json config needed); loaded upfront for any consuming app that runs `boost:install`/`boost:update --discover`. Keep it in sync when the directory convention, generator commands, or repository/criteria wiring change.
- `resources/boost/skills/domain-scaffolding/SKILL.md` — on-demand skill walking through the `domain:*` commands in more depth; update it alongside new generators.

### Testing pattern

Tests live in `tests/Unit/` (no `tests/Feature/` yet despite AGENTS.md mentioning it). Since the package only ships abstract classes, tests extend them inline with concrete anonymous/local implementations to exercise behavior (see `tests/Unit/Actions/ActionTest.php`, `tests/Unit/Repositories/AbstractRepositoryTest.php`). Uses Pest's `describe()`/`it()` syntax with Orchestra Testbench (`tests/TestCase.php`) to bootstrap a Laravel app context.

Console commands (`tests/Unit/Console/Commands/`) are tested by pointing the app at a temp directory — `beforeEach` calls `$this->app->setBasePath(sys_get_temp_dir().'/...')` and `afterEach` deletes it — then driving the command through `Artisan::call()` and asserting on the files it writes, rather than instantiating the command class directly.

## Code Style

- PHP 8.4+, `declare(strict_types=1);` in every file
- Namespace `HarryM\DomainSupport\*` mirrors the `src/` directory structure
- Fully qualified class names in code (no short imports; `global_namespace_import: false` in Pint config)
- PSR-12 formatting, 4-space indent, trailing commas in multi-line arrays, Yoda-style comparisons
- Explicit return types and typed properties throughout
- Naming: camelCase methods, PascalCase classes with role suffixes (`*Action`, `*Repository`, `*Data`), snake_case config keys
- Class member order: traits, constants, properties (public → protected → private), `__construct`, magic methods, methods (public → protected → private)
- Extend `AbstractException` for domain errors (defaults to 400); use `throw_if()` for inline validation
- Use `#[\Override]` when implementing/overriding abstract or parent methods
