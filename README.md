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

The project uses `larastan/larastan` for static analysis. To run the analysis, use:

```bash
composer analyse
```

### Automated Refactoring

The project uses `rector/rector` for automated refactoring. The configuration is in `rector.php`. To run rector, use:

```bash
composer rector
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
