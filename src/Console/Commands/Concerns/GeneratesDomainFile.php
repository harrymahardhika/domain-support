<?php

declare(strict_types=1);

namespace HarryM\DomainSupport\Console\Commands\Concerns;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * @mixin Command
 */
trait GeneratesDomainFile
{
    /**
     * Render a stub into a namespaced class file under app/Domains/{domain}/{subDirectory}.
     *
     * @param array<string,string> $replacements additional `{{ placeholder }}` => value pairs for the stub
     */
    protected function generateDomainFile(
        Filesystem $filesystem,
        string $domain,
        string $name,
        string $subDirectory,
        string $stub,
        array $replacements = [],
    ): int {
        throw_if(
            '' === mb_trim($domain),
            \RuntimeException::class,
            'Domain name must be a non-empty string.'
        );

        throw_if(
            '' === mb_trim($name),
            \RuntimeException::class,
            'Class name must be a non-empty string.'
        );

        $className = Str::studly($name);
        $namespace = sprintf('App\\Domains\\%s\\%s', Str::studly($domain), $subDirectory);
        $path = base_path(sprintf('app/Domains/%s/%s/%s.php', Str::studly($domain), $subDirectory, $className));

        if ($filesystem->exists($path) && ! $this->option('force')) {
            $this->components->error(sprintf('%s already exists.', $path));

            return self::FAILURE;
        }

        $filesystem->ensureDirectoryExists(\dirname($path));

        $content = strtr($this->stubContent($filesystem, $stub), $replacements + [
            '{{ namespace }}' => $namespace,
            '{{ class }}' => $className,
        ]);

        $filesystem->put($path, $content);

        $this->components->info(sprintf('%s created successfully.', $path));

        return self::SUCCESS;
    }

    /**
     * Resolve stub content, preferring a copy published into the consuming app.
     */
    protected function stubContent(Filesystem $filesystem, string $stub): string
    {
        $published = base_path(sprintf('stubs/domain-support/%s', $stub));

        if ($filesystem->exists($published)) {
            return $filesystem->get($published);
        }

        return $filesystem->get(\sprintf('%s/../../../../stubs/%s', __DIR__, $stub));
    }

    /**
     * Fetch a required console argument, guaranteed to be a string.
     */
    protected function stringArgument(string $key): string
    {
        $value = $this->argument($key);

        throw_if(\is_array($value), \RuntimeException::class, sprintf('Argument [%s] must be a string.', $key));

        return (string) $value;
    }

    /**
     * Fetch a console option, guaranteed to be a string.
     */
    protected function stringOption(string $key): string
    {
        $value = $this->option($key);

        throw_if(\is_array($value), \RuntimeException::class, sprintf('Option [%s] must be a string.', $key));

        return (string) $value;
    }
}
