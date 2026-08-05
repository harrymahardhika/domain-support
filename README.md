# DomainSupport

This project is a Laravel package named `harrym/domain-support`. It provides a set of abstract classes and utilities to support a domain-driven architecture within a Laravel application. The package promotes a structured and consistent way of building applications by providing base classes for common architectural components like actions, repositories, models, and controllers.

The package is built for PHP 8.4+ and Laravel. It has a key dependency on `spatie/laravel-data`.

## Key Concepts

The package provides the following abstract classes to be extended by the application's concrete classes:

- **`AbstractAction`**: Used for business logic. Actions are dispatchable and have a `handle` method where the logic is implemented.
- **`AbstractConstant`**: A utility class to manage and retrieve constants from a class.
- **`AbstractAPIController`**: A base controller for API endpoints. It includes the `SendsJsonResponse` trait for standardized JSON responses.
- **`AbstractWebController`**: A base controller for web routes.
- **`AbstractEvent`**: A base class for domain events, incorporating common Laravel event traits.
- **`AbstractException`**: A custom exception base class.
- **`AbstractModel`**: A base Eloquent model that includes `SoftDeletes` and `HasFactory` traits, and configures pagination based on the `domain-support.php` config file.
- **`AbstractRepository`**: A repository pattern implementation that provides a fluent interface for querying data. It supports searching, sorting, pagination, and filtering using a `CriteriaInterface`.

## Installation

Install the package via Composer:

```bash
composer require harrym/domain-support
```

### Configuration

The package's configuration file can be published using the following command:

```bash
php artisan vendor:publish --provider="HarryM\DomainSupport\DomainSupportServiceProvider" --tag="domain-support.config"
```

This will create a `config/domain-support.php` file in the application's config directory.

## Console Commands

Scaffold a domain's directory skeleton:

```bash
php artisan domain:create-domain Blog
```

Generate individual classes into that skeleton, each extending the matching abstract base class:

```bash
php artisan domain:make-model Blog Post
php artisan domain:make-repository Blog PostRepository            # --model= to override the guessed model FQCN
php artisan domain:make-criteria Blog PostCriteria
php artisan domain:make-controller Blog PostController            # --web for AbstractWebController
php artisan domain:make-action Blog PublishPost                   # --async for AbstractAsyncAction
php artisan domain:make-exception Blog PostNotFound                # --code= to set the default HTTP status (400)
php artisan domain:make-event Blog PostPublished
php artisan domain:make-constant Blog PostStatus
php artisan domain:make-enum Blog PostStatus
```

All of the above accept `--force` to overwrite an existing file. Stubs can be customized per-application by publishing them:

```bash
php artisan vendor:publish --provider="HarryM\DomainSupport\DomainSupportServiceProvider" --tag="domain-support.stubs"
```

`domain:make-crud {domain} {name}` runs the model/repository/criteria/controller/action generators together for a single resource. `domain:list` prints the existing domains under `app/Domains` and a summary of what each one contains.

## Laravel Boost Integration

This package ships [Laravel Boost](https://laravel.com/docs/boost) AI guidelines (`resources/boost/guidelines/core.blade.php`) and a skill (`resources/boost/skills/domain-scaffolding/SKILL.md`), so downstream AI agents automatically learn its conventions and generator commands. In a consuming application with Boost installed, run:

```bash
php artisan boost:install       # or: php artisan boost:update --discover
```

and select `harrym/domain-support` when prompted to pull in its guidelines and skill.

## Testing and Development

### Testing

The project uses Pest and PHPUnit for testing. The tests are located in the `tests` directory. To run the tests, use the following command:

```bash
composer test
```

### Coding Style

The project uses `laravel/pint` for code style. To format the code, run:

```bash
composer pint
```

### Static Analysis

The project uses `larastan/larastan` for static analysis (level 9). To run the analysis, use:

```bash
./vendor/bin/phpstan analyse --memory-limit=1G
```

### Automated Refactoring

The project uses `rector/rector` for automated refactoring. The configuration is in `rector.php`. To run rector, use:

```bash
composer rector
```

### Releasing

This package has no `version` field in `composer.json` — Composer and Packagist resolve the installed version from git tags, so cutting a release means creating a new tag. `scripts/bump-version.sh` automates this: it reads the latest `MAJOR.MINOR.PATCH` tag, bumps the requested part, and creates an annotated tag with that version.

```bash
composer release              # bump patch (default) and create a local tag
composer release minor        # bump minor
composer release major        # bump major
composer release -- -m "message"   # use a custom tag message (defaults to the latest commit subject)
composer release -- --push    # also push the current branch and tag to origin
```

The script refuses to run on a dirty working tree and will not overwrite an existing tag. Without `--push`, the tag is created locally only — push it yourself with `git push origin <branch> --follow-tags` when ready.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
