# Laravel Domain Support - Agent Guidelines

## Commands
- `composer install` — install dependencies
- `./vendor/bin/pest` — run all tests (Pest with Orchestra Testbench)
- `./vendor/bin/pest tests/Unit/Actions/ActionTest.php` — run single test file
- `./vendor/bin/pest --filter "test name"` — run specific test by name
- `./vendor/bin/pest --coverage` — run tests with coverage
- `./vendor/bin/phpstan analyse --memory-limit=1G` — static analysis (level 9)
- `./vendor/bin/pint` — auto-format code (PSR-12 + Laravel preset)
- `./vendor/bin/rector process` — apply refactoring rules

## Code Style
- **PHP version**: PHP 8.4+ (declare strict types: `declare(strict_types=1);`)
- **Namespace**: All classes under `HarryM\DomainSupport\*`, matching directory structure
- **Imports**: Fully qualified class names (no short imports) via `global_namespace_import: false`
- **Formatting**: PSR-12, 4-space indent, trailing commas in multi-line arrays, yoda style comparisons
- **Types**: Explicit return types and typed properties required throughout
- **Naming**: camelCase methods, PascalCase classes with suffixes (`*Action`, `*Repository`, `*Data`), snake_case config keys
- **Class order**: use_trait, constants, properties (public→protected→private), __construct, magic methods, public→protected→private methods
- **Error handling**: Extend `AbstractException` (defaults to 400 code), use `throw_if()` for inline validation
- **Attributes**: Use `#[\Override]` when implementing abstract methods

## Testing
- Place unit tests in `tests/Unit/`, feature tests in `tests/Feature/`
- Use Pest's `describe()` and `it()` syntax with descriptive names
- Prefer constructor injection and data objects over facades for deterministic tests
- Test concrete implementations by extending abstract classes inline
- Test console commands by pointing the app at a temp directory (`$this->app->setBasePath(...)` in `beforeEach`, deleted in `afterEach`) and driving them via `Artisan::call()`, asserting on the files written — see `tests/Unit/Console/Commands/`

## Console Command Generators
- `Console/Commands/Make*` classes render a `stubs/*.stub` file into `app/Domains/{domain}/{Type}/{Name}.php`; shared logic lives in `Console/Commands/Concerns/GeneratesDomainFile`
- When adding a new generator: add its stub under `stubs/`, register the command in `DomainSupportServiceProvider`, add coverage in `tests/Unit/Console/Commands/`, and update `resources/boost/guidelines/core.blade.php` / `resources/boost/skills/domain-scaffolding/SKILL.md` so downstream AI agents using Laravel Boost stay in sync
- Stub content is resolved from an app-published `stubs/domain-support/` override first, then the package's own `stubs/` — keep that lookup in sync if the stub directory moves
