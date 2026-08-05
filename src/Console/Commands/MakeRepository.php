<?php

declare(strict_types=1);

namespace HarryM\DomainSupport\Console\Commands;

use HarryM\DomainSupport\Console\Commands\Concerns\GeneratesDomainFile;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeRepository extends Command
{
    use GeneratesDomainFile;

    public $signature = 'domain:make-repository {domain} {name} {--model= : The model FQCN this repository queries (defaults to a same-domain model named after the repository)} {--force : Overwrite the file if it already exists}';

    public $description = 'Create a new domain repository';

    public function handle(Filesystem $filesystem): int
    {
        $domain = $this->stringArgument('domain');
        $name = $this->stringArgument('name');

        $model = $this->resolveModelClass($domain, $name);

        return $this->generateDomainFile(
            $filesystem,
            $domain,
            $name,
            'Repositories',
            'repository.stub',
            [
                '{{ model }}' => $model,
                '{{ modelClass }}' => Str::afterLast($model, '\\'),
            ],
        );
    }

    protected function resolveModelClass(string $domain, string $name): string
    {
        $modelOption = $this->option('model');

        if (\is_string($modelOption) && '' !== mb_trim($modelOption)) {
            return $modelOption;
        }

        $modelName = Str::studly(Str::replaceLast('Repository', '', Str::studly($name)));

        return sprintf('App\\Domains\\%s\\Models\\%s', Str::studly($domain), $modelName);
    }
}
