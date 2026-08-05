<?php

declare(strict_types=1);

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;

beforeEach(function (): void {
    $this->basePath = sys_get_temp_dir().'/domain-support-tests-'.uniqid();
    $this->files = new Filesystem;
    $this->files->makeDirectory($this->basePath, 0755, true);
    $this->app->setBasePath($this->basePath);
});

afterEach(function (): void {
    $this->files->deleteDirectory($this->basePath);
});

describe('domain:make-action', function (): void {
    it('creates a sync action extending AbstractAction', function (): void {
        Artisan::call('domain:make-action', ['domain' => 'Blog', 'name' => 'PublishPost']);

        $path = $this->basePath.'/app/Domains/Blog/Actions/PublishPost.php';

        expect($this->files->exists($path))->toBeTrue();
        expect($this->files->get($path))
            ->toContain('namespace App\Domains\Blog\Actions;')
            ->toContain('class PublishPost extends AbstractAction')
            ->toContain('use HarryM\DomainSupport\Actions\AbstractAction;');
    });

    it('creates an async action when --async is passed', function (): void {
        Artisan::call('domain:make-action', ['domain' => 'Blog', 'name' => 'PublishPost', '--async' => true]);

        $path = $this->basePath.'/app/Domains/Blog/Actions/PublishPost.php';

        expect($this->files->get($path))
            ->toContain('class PublishPost extends AbstractAsyncAction')
            ->toContain('use HarryM\DomainSupport\Actions\AbstractAsyncAction;');
    });

    it('studly-cases the domain and class name', function (): void {
        Artisan::call('domain:make-action', ['domain' => 'blog_posts', 'name' => 'publish post']);

        expect($this->files->exists($this->basePath.'/app/Domains/BlogPosts/Actions/PublishPost.php'))->toBeTrue();
    });

    it('refuses to overwrite an existing file without --force', function (): void {
        Artisan::call('domain:make-action', ['domain' => 'Blog', 'name' => 'PublishPost']);
        $path = $this->basePath.'/app/Domains/Blog/Actions/PublishPost.php';
        $this->files->put($path, 'untouched');

        $exitCode = Artisan::call('domain:make-action', ['domain' => 'Blog', 'name' => 'PublishPost']);

        expect($exitCode)->toBe(Command::FAILURE);
        expect($this->files->get($path))->toBe('untouched');
    });

    it('overwrites an existing file with --force', function (): void {
        Artisan::call('domain:make-action', ['domain' => 'Blog', 'name' => 'PublishPost']);
        $path = $this->basePath.'/app/Domains/Blog/Actions/PublishPost.php';
        $this->files->put($path, 'untouched');

        $exitCode = Artisan::call('domain:make-action', ['domain' => 'Blog', 'name' => 'PublishPost', '--force' => true]);

        expect($exitCode)->toBe(Command::SUCCESS);
        expect($this->files->get($path))->toContain('class PublishPost extends AbstractAction');
    });
});

describe('domain:make-repository', function (): void {
    it('defaults the model to a same-domain model named after the repository', function (): void {
        Artisan::call('domain:make-repository', ['domain' => 'Blog', 'name' => 'PostRepository']);

        $content = $this->files->get($this->basePath.'/app/Domains/Blog/Repositories/PostRepository.php');

        expect($content)
            ->toContain('use App\Domains\Blog\Models\Post;')
            ->toContain('protected string $model = Post::class;');
    });

    it('respects an explicit --model option', function (): void {
        Artisan::call('domain:make-repository', [
            'domain' => 'Blog',
            'name' => 'PostRepository',
            '--model' => 'App\\Domains\\Blog\\Models\\Article',
        ]);

        $content = $this->files->get($this->basePath.'/app/Domains/Blog/Repositories/PostRepository.php');

        expect($content)
            ->toContain('use App\Domains\Blog\Models\Article;')
            ->toContain('protected string $model = Article::class;');
    });
});

describe('domain:make-criteria', function (): void {
    it('creates a criteria class extending AbstractCriteria', function (): void {
        Artisan::call('domain:make-criteria', ['domain' => 'Blog', 'name' => 'PostCriteria']);

        $content = $this->files->get($this->basePath.'/app/Domains/Blog/Repositories/PostCriteria.php');

        expect($content)->toContain('class PostCriteria extends AbstractCriteria');
    });
});

describe('domain:make-model', function (): void {
    it('creates a model extending AbstractModel', function (): void {
        Artisan::call('domain:make-model', ['domain' => 'Blog', 'name' => 'Post']);

        $content = $this->files->get($this->basePath.'/app/Domains/Blog/Models/Post.php');

        expect($content)->toContain('class Post extends AbstractModel');
    });
});

describe('domain:make-controller', function (): void {
    it('defaults to an API controller', function (): void {
        Artisan::call('domain:make-controller', ['domain' => 'Blog', 'name' => 'PostController']);

        $content = $this->files->get($this->basePath.'/app/Domains/Blog/Controllers/PostController.php');

        expect($content)->toContain('class PostController extends AbstractAPIController');
    });

    it('creates a web controller when --web is passed', function (): void {
        Artisan::call('domain:make-controller', ['domain' => 'Blog', 'name' => 'PostController', '--web' => true]);

        $content = $this->files->get($this->basePath.'/app/Domains/Blog/Controllers/PostController.php');

        expect($content)->toContain('class PostController extends AbstractWebController');
    });
});

describe('domain:make-exception', function (): void {
    it('defaults the status code to 400', function (): void {
        Artisan::call('domain:make-exception', ['domain' => 'Blog', 'name' => 'PostNotFound']);

        $content = $this->files->get($this->basePath.'/app/Domains/Blog/Exceptions/PostNotFound.php');

        expect($content)->toContain('int $code = 400');
    });

    it('honours the --code option', function (): void {
        Artisan::call('domain:make-exception', ['domain' => 'Blog', 'name' => 'PostNotFound', '--code' => '404']);

        $content = $this->files->get($this->basePath.'/app/Domains/Blog/Exceptions/PostNotFound.php');

        expect($content)->toContain('int $code = 404');
    });
});

describe('domain:make-event', function (): void {
    it('creates an event extending AbstractEvent', function (): void {
        Artisan::call('domain:make-event', ['domain' => 'Blog', 'name' => 'PostPublished']);

        $content = $this->files->get($this->basePath.'/app/Domains/Blog/Events/PostPublished.php');

        expect($content)->toContain('class PostPublished extends AbstractEvent');
    });
});

describe('domain:make-constant', function (): void {
    it('creates a constant class extending AbstractConstant', function (): void {
        Artisan::call('domain:make-constant', ['domain' => 'Blog', 'name' => 'PostStatus']);

        $content = $this->files->get($this->basePath.'/app/Domains/Blog/Constants/PostStatus.php');

        expect($content)->toContain('class PostStatus extends AbstractConstant');
    });
});

describe('domain:make-enum', function (): void {
    it('creates a backed enum using EnumTrait', function (): void {
        Artisan::call('domain:make-enum', ['domain' => 'Blog', 'name' => 'PostStatus']);

        $content = $this->files->get($this->basePath.'/app/Domains/Blog/Enums/PostStatus.php');

        expect($content)
            ->toContain('enum PostStatus: string')
            ->toContain('use HarryM\DomainSupport\Enums\EnumTrait;');
    });
});

describe('domain:make-crud', function (): void {
    it('generates a model, repository, criteria, controller, and action', function (): void {
        $exitCode = Artisan::call('domain:make-crud', ['domain' => 'Blog', 'name' => 'Post']);

        expect($exitCode)->toBe(Command::SUCCESS);
        expect($this->files->exists($this->basePath.'/app/Domains/Blog/Models/Post.php'))->toBeTrue();
        expect($this->files->exists($this->basePath.'/app/Domains/Blog/Repositories/PostRepository.php'))->toBeTrue();
        expect($this->files->exists($this->basePath.'/app/Domains/Blog/Repositories/PostCriteria.php'))->toBeTrue();
        expect($this->files->exists($this->basePath.'/app/Domains/Blog/Controllers/PostController.php'))->toBeTrue();
        expect($this->files->exists($this->basePath.'/app/Domains/Blog/Actions/PostAction.php'))->toBeTrue();
    });

    it('stops and reports failure when a step refuses to overwrite an existing file', function (): void {
        Artisan::call('domain:make-crud', ['domain' => 'Blog', 'name' => 'Post']);
        $this->files->put($this->basePath.'/app/Domains/Blog/Models/Post.php', 'untouched');

        $exitCode = Artisan::call('domain:make-crud', ['domain' => 'Blog', 'name' => 'Post']);

        expect($exitCode)->toBe(Command::FAILURE);
    });

    it('forwards --force to every step', function (): void {
        Artisan::call('domain:make-crud', ['domain' => 'Blog', 'name' => 'Post']);
        $this->files->put($this->basePath.'/app/Domains/Blog/Models/Post.php', 'untouched');

        $exitCode = Artisan::call('domain:make-crud', ['domain' => 'Blog', 'name' => 'Post', '--force' => true]);

        expect($exitCode)->toBe(Command::SUCCESS);
        expect($this->files->get($this->basePath.'/app/Domains/Blog/Models/Post.php'))
            ->toContain('class Post extends AbstractModel');
    });
});

describe('stub publishing', function (): void {
    it('prefers a stub published into the consuming app', function (): void {
        $publishedPath = $this->basePath.'/stubs/domain-support/action.stub';
        $this->files->ensureDirectoryExists(\dirname($publishedPath));
        $this->files->put($publishedPath, "<?php\n\nnamespace {{ namespace }};\n\nclass {{ class }}\n{\n    // published override\n}\n");

        Artisan::call('domain:make-action', ['domain' => 'Blog', 'name' => 'PublishPost']);

        $content = $this->files->get($this->basePath.'/app/Domains/Blog/Actions/PublishPost.php');

        expect($content)->toContain('// published override');
    });
});
