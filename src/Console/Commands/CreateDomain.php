<?php

declare(strict_types=1);

namespace HarryM\DomainSupport\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class CreateDomain extends Command
{
    public $signature = 'domain:create-domain {domain}';

    public $description = 'Create domain directories';

    public function handle(Filesystem $filesystem): int
    {
        $domainName = $this->argument('domain');

        throw_if(
            ! is_string($domainName) || '' === mb_trim($domainName),
            \RuntimeException::class,
            'Domain name must be a non-empty string.'
        );

        $directories = [
            'Actions',
            'Controllers',
            'DTO',
            'Enums',
            'Exceptions',
            'Models',
            'Repositories',
            'Requests',
        ];

        foreach ($directories as $dir) {
            $path = base_path(sprintf('app/Domains/%s/%s', $domainName, $dir));

            if (! $filesystem->isDirectory($path)) {
                $filesystem->makeDirectory($path, 0755, true);
            }

            $filesystem->put($path.'/.gitkeep', '');
        }

        return self::SUCCESS;
    }
}
