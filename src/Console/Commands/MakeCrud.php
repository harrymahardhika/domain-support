<?php

declare(strict_types=1);

namespace HarryM\DomainSupport\Console\Commands;

use HarryM\DomainSupport\Console\Commands\Concerns\GeneratesDomainFile;
use Illuminate\Console\Command;

class MakeCrud extends Command
{
    use GeneratesDomainFile;

    public $signature = 'domain:make-crud {domain} {name} {--force : Overwrite files that already exist}';

    public $description = 'Create a model, repository, criteria, controller, and action for a domain resource';

    public function handle(): int
    {
        $domain = $this->stringArgument('domain');
        $name = $this->stringArgument('name');
        $force = (bool) $this->option('force');

        $steps = [
            ['command' => 'domain:make-model', 'name' => $name],
            ['command' => 'domain:make-repository', 'name' => $name.'Repository'],
            ['command' => 'domain:make-criteria', 'name' => $name.'Criteria'],
            ['command' => 'domain:make-controller', 'name' => $name.'Controller'],
            ['command' => 'domain:make-action', 'name' => $name.'Action'],
        ];

        foreach ($steps as $step) {
            $exitCode = $this->call($step['command'], [
                'domain' => $domain,
                'name' => $step['name'],
                '--force' => $force,
            ]);

            if (self::SUCCESS !== $exitCode) {
                return $exitCode;
            }
        }

        return self::SUCCESS;
    }
}
