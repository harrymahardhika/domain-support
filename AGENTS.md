# Laravel Domain Support - Agent Guidelines

## Commands
- `composer install` — install dependencies
- `./vendor/bin/pest` — run all tests (Pest with Orchestra Testbench)
- `./vendor/bin/pest tests/Unit/Actions/ActionTest.php` — run single test file
- `./vendor/bin/pest --filter "test name"` — run specific test by name
- `./vendor/bin/pest --coverage` — run tests with coverage
- `./vendor/bin/phpstan analyse --memory-limit=1G` — static analysis (level 8)
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
