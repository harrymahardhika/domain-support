# Contributing

Before opening a PR:

```bash
composer pint                                     # code style
./vendor/bin/phpstan analyse --memory-limit=1G    # static analysis (level 9, via larastan)
composer test                                     # pest
```

## Adding a new domain generator command

The `domain:make-*` commands each render a stub from `stubs/` into `app/Domains/{domain}/{Type}/{Name}.php`. To add one:

1. Add a stub to `stubs/` following the existing naming (`{type}.stub`), with `{{ namespace }}` and `{{ class }}` placeholders (plus any command-specific ones, e.g. `{{ model }}`).
2. Add a `Console/Commands/Make{Type}` class using the `Console\Commands\Concerns\GeneratesDomainFile` trait — see `MakeAction` for the minimal shape.
3. Register the command in `DomainSupportServiceProvider::bootForConsole()`.
4. Add coverage in `tests/Unit/Console/Commands/`, following the temp-`base_path` + `Artisan::call()` pattern used by the existing tests.
5. Update `README.md`'s Console Commands section.
6. Update `resources/boost/guidelines/core.blade.php` and `resources/boost/skills/domain-scaffolding/SKILL.md` — these are what downstream AI agents using [Laravel Boost](https://laravel.com/docs/boost) actually read, and will go stale silently otherwise.

See `CLAUDE.md` / `AGENTS.md` for the rest of the project's architecture and code style conventions.
