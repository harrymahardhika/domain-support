<?php

declare(strict_types=1);

namespace HarryM\DomainSupport\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;

class ListDomains extends Command
{
    public $signature = 'domain:list';

    public $description = 'List the domains under app/Domains and what each one contains';

    public function handle(Filesystem $filesystem): int
    {
        $domainsPath = base_path('app/Domains');

        if (! $filesystem->isDirectory($domainsPath)) {
            $this->components->warn('No domains found. Run domain:create-domain to create one.');

            return self::SUCCESS;
        }

        $rows = Collection::make($filesystem->directories($domainsPath))
            ->map(function (string $path) use ($filesystem): array {
                $counts = Collection::make($filesystem->directories($path))
                    ->mapWithKeys(fn (string $subDirectory): array => [
                        basename($subDirectory) => \count($filesystem->files($subDirectory)),
                    ])
                    ->filter(fn (int $count): bool => $count > 0)
                    ->map(fn (int $count, string $subDirectory): string => sprintf('%s (%d)', $subDirectory, $count))
                    ->implode(', ');

                return [basename($path), '' === $counts ? '—' : $counts];
            })
            ->sortBy(fn (array $row): string => $row[0])
            ->values()
            ->all();

        if ([] === $rows) {
            $this->components->warn('No domains found. Run domain:create-domain to create one.');

            return self::SUCCESS;
        }

        $this->table(['Domain', 'Contents'], $rows);

        return self::SUCCESS;
    }
}
