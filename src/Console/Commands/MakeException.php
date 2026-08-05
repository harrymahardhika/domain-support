<?php

declare(strict_types=1);

namespace HarryM\DomainSupport\Console\Commands;

use HarryM\DomainSupport\Console\Commands\Concerns\GeneratesDomainFile;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakeException extends Command
{
    use GeneratesDomainFile;

    public $signature = 'domain:make-exception {domain} {name} {--code=400 : The default HTTP status code for the exception} {--force : Overwrite the file if it already exists}';

    public $description = 'Create a new domain exception';

    public function handle(Filesystem $filesystem): int
    {
        return $this->generateDomainFile(
            $filesystem,
            $this->stringArgument('domain'),
            $this->stringArgument('name'),
            'Exceptions',
            'exception.stub',
            ['{{ code }}' => (string) (int) $this->option('code')],
        );
    }
}
