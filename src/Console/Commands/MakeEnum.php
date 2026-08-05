<?php

declare(strict_types=1);

namespace HarryM\DomainSupport\Console\Commands;

use HarryM\DomainSupport\Console\Commands\Concerns\GeneratesDomainFile;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakeEnum extends Command
{
    use GeneratesDomainFile;

    public $signature = 'domain:make-enum {domain} {name} {--force : Overwrite the file if it already exists}';

    public $description = 'Create a new domain backed enum using EnumTrait';

    public function handle(Filesystem $filesystem): int
    {
        return $this->generateDomainFile(
            $filesystem,
            $this->stringArgument('domain'),
            $this->stringArgument('name'),
            'Enums',
            'enum.stub',
        );
    }
}
