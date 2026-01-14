<?php

declare(strict_types=1);

use HarryM\DomainSupport\Controllers\AbstractAPIController;
use HarryM\DomainSupport\Controllers\AbstractWebController;
use HarryM\DomainSupport\Controllers\HasPermissionMiddleware;
use Illuminate\Routing\Controller;

// Test controller implementations
class TestAPIController extends AbstractAPIController
{
    public function __construct()
    {
        $this->requiresPermissions([
            'posts.view' => 'index',
            'posts.create' => ['create', 'store'],
            'posts.edit' => ['edit', 'update'],
            'posts.delete' => 'destroy',
        ]);
    }

    public function index(): string
    {
        return 'index';
    }

    public function create(): string
    {
        return 'create';
    }

    public function store(): string
    {
        return 'store';
    }

    public function edit(): string
    {
        return 'edit';
    }

    public function update(): string
    {
        return 'update';
    }

    public function destroy(): string
    {
        return 'destroy';
    }

    public function getMiddlewareForTesting(): array
    {
        return $this->middleware;
    }
}

class TestWebController extends AbstractWebController
{
    public function __construct()
    {
        $this->requiresPermissions([
            'users.manage' => ['index', 'show'],
            'users.create' => 'create',
        ]);
    }

    public function index(): string
    {
        return 'index';
    }

    public function show(): string
    {
        return 'show';
    }

    public function create(): string
    {
        return 'create';
    }

    public function getMiddlewareForTesting(): array
    {
        return $this->middleware;
    }
}

describe('HasPermissionMiddleware trait', function (): void {
    describe('Trait structure', function (): void {
        it('is used by AbstractAPIController', function (): void {
            $traits = class_uses_recursive(AbstractAPIController::class);

            expect($traits)->toContain(HasPermissionMiddleware::class);
        });

        it('is used by AbstractWebController', function (): void {
            $traits = class_uses_recursive(AbstractWebController::class);

            expect($traits)->toContain(HasPermissionMiddleware::class);
        });

        it('has requiresPermissions method', function (): void {
            $reflection = new ReflectionClass(HasPermissionMiddleware::class);

            expect($reflection->hasMethod('requiresPermissions'))->toBeTrue();
        });
    });

    describe('API Controller middleware application', function (): void {
        it('applies single method permission middleware', function (): void {
            $controller = new TestAPIController();
            $middleware = $controller->getMiddlewareForTesting();

            expect($middleware)->toBeArray();
            expect($middleware)->toHaveCount(4);
        });

        it('applies permission middleware with correct format', function (): void {
            $controller = new TestAPIController();
            $middleware = $controller->getMiddlewareForTesting();

            $indexMiddleware = collect($middleware)->first(
                fn ($m): bool => isset($m['options']['only']) && in_array('index', $m['options']['only'])
            );

            expect($indexMiddleware)->not->toBeNull();
            expect($indexMiddleware['middleware'])->toContain('permission:posts.view');
        });

        it('applies permission middleware to multiple methods', function (): void {
            $controller = new TestAPIController();
            $middleware = $controller->getMiddlewareForTesting();

            $createMiddleware = collect($middleware)->first(
                fn ($m): bool => isset($m['options']['only']) && in_array('create', $m['options']['only']) && in_array('store', $m['options']['only'])
            );

            expect($createMiddleware)->not->toBeNull();
            expect($createMiddleware['middleware'])->toContain('permission:posts.create');
            expect($createMiddleware['options']['only'])->toBe(['create', 'store']);
        });

        it('handles array of methods correctly', function (): void {
            $controller = new TestAPIController();
            $middleware = $controller->getMiddlewareForTesting();

            $editMiddleware = collect($middleware)->first(
                fn ($m): bool => isset($m['options']['only']) && in_array('edit', $m['options']['only']) && in_array('update', $m['options']['only'])
            );

            expect($editMiddleware)->not->toBeNull();
            expect($editMiddleware['options']['only'])->toBe(['edit', 'update']);
        });
    });

    describe('Web Controller middleware application', function (): void {
        it('applies middleware to web controller', function (): void {
            $controller = new TestWebController();
            $middleware = $controller->getMiddlewareForTesting();

            expect($middleware)->toBeArray();
            expect($middleware)->toHaveCount(2);
        });

        it('applies multiple methods to single permission', function (): void {
            $controller = new TestWebController();
            $middleware = $controller->getMiddlewareForTesting();

            $manageMiddleware = collect($middleware)->first(
                fn ($m): bool => isset($m['options']['only']) && in_array('index', $m['options']['only'])
            );

            expect($manageMiddleware)->not->toBeNull();
            expect($manageMiddleware['middleware'])->toContain('permission:users.manage');
            expect($manageMiddleware['options']['only'])->toBe(['index', 'show']);
        });
    });

    describe('Edge cases', function (): void {
        it('handles empty permissions array', function (): void {
            $controller = new class() extends AbstractAPIController
            {
                public function __construct()
                {
                    $this->requiresPermissions([]);
                }

                public function getMiddlewareForTesting(): array
                {
                    return $this->middleware;
                }
            };

            expect($controller->getMiddlewareForTesting())->toBeArray();
        });

        it('converts single method string to array internally', function (): void {
            $controller = new TestAPIController();
            $middleware = $controller->getMiddlewareForTesting();

            $destroyMiddleware = collect($middleware)->first(
                fn ($m): bool => isset($m['options']['only']) && in_array('destroy', $m['options']['only'])
            );

            expect($destroyMiddleware)->not->toBeNull();
            expect($destroyMiddleware['options']['only'])->toBe(['destroy']);
        });
    });

    describe('Inheritance', function (): void {
        it('API controller extends base Controller', function (): void {
            expect(AbstractAPIController::class)
                ->toExtend(Controller::class);
        });

        it('Web controller extends base Controller', function (): void {
            expect(AbstractWebController::class)
                ->toExtend(Controller::class);
        });

        it('trait method is accessible in concrete controllers', function (): void {
            $apiController = new TestAPIController();
            $webController = new TestWebController();

            expect(method_exists($apiController, 'requiresPermissions'))->toBeTrue();
            expect(method_exists($webController, 'requiresPermissions'))->toBeTrue();
        });
    });
});
