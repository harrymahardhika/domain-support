# Testing Locally in a Laravel Project

To test this package locally in a Laravel project before publishing it to Packagist, you can use Composer's "path" repository feature. This will allow you to use your local package as a dependency in a Laravel project.

Here's a step-by-step guide:

### 1. Create a new Laravel Project (if you don't have one)

First, you'll need a Laravel project to test your package in. If you don't have one, you can create a new one:

```bash
composer create-project laravel/laravel my-test-app
cd my-test-app
```

### 2. Configure the "path" Repository

In your Laravel project's `composer.json` file, add a `repositories` section to point to your local package directory. You'll need to use the absolute path to your package.

Assuming your package is in `/path/to/your-package`, you would add the following to the `composer.json` of `my-test-app`:

```json
"repositories": [
    {
        "type": "path",
        "url": "/path/to/your-package"
    }
]
```

### 3. Require the Package

Now, from within your Laravel project's directory (`my-test-app`), you can require your package using Composer:

```bash
composer require harrym/domain-support
```

Composer will see the "path" repository and create a symlink from your project's `vendor/harrym/domain-support` directory to your local package directory (`/path/to/your-package`).

### 4. Use the Package

Your package's service provider should be automatically discovered by Laravel. You can now use the classes and features from your package within your Laravel application.

Any changes you make in your package's source code will be immediately available in your `my-test-app` project, so you can test your package in a real-world scenario.

### 5. Publish Configuration (Optional)

You can also test the publishing of your configuration file:

```bash
php artisan vendor:publish --provider="HarryM\DomainSupport\DomainSupportServiceProvider" --tag="domain-support.config"
```

This will publish the `domain-support.php` config file to the `config` directory of your Laravel application.
