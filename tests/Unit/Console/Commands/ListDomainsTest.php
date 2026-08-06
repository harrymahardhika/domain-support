<?php

declare(strict_types=1);

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

it('warns when no app/Domains directory exists', function (): void {
    Artisan::call('domain:list');

    expect(Artisan::output())->toContain('No domains found.');
});

it('lists domains alphabetically with a summary of their contents', function (): void {
    Artisan::call('domain:create-domain', ['domain' => 'Blog']);
    Artisan::call('domain:create-domain', ['domain' => 'Accounts']);
    Artisan::call('domain:make-model', ['domain' => 'Blog', 'name' => 'Post']);
    Artisan::call('domain:make-model', ['domain' => 'Blog', 'name' => 'Comment']);

    Artisan::call('domain:list');
    $output = Artisan::output();

    expect(mb_strpos($output, 'Accounts'))->toBeLessThan(mb_strpos($output, 'Blog'));
    expect($output)->toContain('Models (2)');
});
