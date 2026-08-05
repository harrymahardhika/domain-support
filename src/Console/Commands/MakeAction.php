<?php

declare(strict_types=1);

namespace HarryM\DomainSupport\Console\Commands;

use HarryM\DomainSupport\Console\Commands\Concerns\GeneratesDomainFile;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakeAction extends Command
{
    use GeneratesDomainFile;

    public $signature = 'domain:make-action {domain} {name} {--async : Generate an AbstractAsyncAction instead} {--force : Overwrite the file if it already exists}';

    public $description = 'Create a new domain action';

    public function handle(Filesystem $filesystem): int
    {
        return $this->generateDomainFile(
            $filesystem,
            $this->stringArgument('domain'),
            $this->stringArgument('name'),
            'Actions',
            $this->option('async') ? 'async-action.stub' : 'action.stub',
        );
    }
}
