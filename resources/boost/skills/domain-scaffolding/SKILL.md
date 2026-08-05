---
name: domain-scaffolding
description: Scaffold a new domain or resource in an app using harrym/domain-support's Artisan generators (domain:create-domain, domain:make-*, domain:make-crud) instead of hand-writing classes that extend its abstract base classes.
---

# Domain Scaffolding with harrym/domain-support

## When to use this skill

Use this whenever the task is to add a new domain, or a new action/repository/model/controller/exception/event/constant/enum inside an existing domain, in an application that depends on `harrym/domain-support`. Prefer the generators below over writing the class by hand — they wire the correct namespace, base class, and directory for you.

## 1. Creating a new domain

```bash
php artisan domain:create-domain Blog
```

Creates the empty skeleton: `app/Domains/Blog/{Actions,Constants,Controllers,DTO,Enums,Events,Exceptions,Models,Repositories,Requests}`.

## 2. Generating individual classes

Each command below writes `app/Domains/{domain}/{Type}/{Name}.php` extending the matching abstract class. All accept `--force` to overwrite.

| Command | Base class | Notable options |
| --- | --- | --- |
| `domain:make-model {domain} {name}` | `AbstractModel` | |
| `domain:make-repository {domain} {name}` | `AbstractRepository` | `--model=` (FQCN; defaults to a same-domain model named after the repository, e.g. `PostRepository` → `Post`) |
| `domain:make-criteria {domain} {name}` | `AbstractCriteria` | |
| `domain:make-controller {domain} {name}` | `AbstractAPIController` | `--web` for `AbstractWebController` |
| `domain:make-action {domain} {name}` | `AbstractAction` | `--async` for `AbstractAsyncAction` |
| `domain:make-exception {domain} {name}` | `AbstractException` | `--code=` (default `400`) |
| `domain:make-event {domain} {name}` | `AbstractEvent` | |
| `domain:make-constant {domain} {name}` | `AbstractConstant` | |
| `domain:make-enum {domain} {name}` | backed enum + `EnumTrait` | |

Names are studly-cased automatically (`publish post` → `PublishPost`), so pass whatever casing is natural.

## 3. Scaffolding a full CRUD resource

`domain:make-crud {domain} {name}` chains model, repository, criteria, controller, and action generators for one resource:

```bash
php artisan domain:make-crud Blog Post
# creates:
#   Models/Post.php
#   Repositories/PostRepository.php
#   Repositories/PostCriteria.php
#   Controllers/PostController.php
#   Actions/PostAction.php
```

It stops at the first step that already exists (returns non-zero) unless `--force` is passed, in which case `--force` is forwarded to every step.

## 4. After generating

- `Models/{Name}.php`: fill in `newFactory()` if the model needs a factory; it stubs to `return null;` by default.
- `Repositories/{Name}Repository.php`: fill in `$sortableColumns`, `$searchableColumns`, and `$with`; add a public method per extra criteria field you want to support (see the `Repository + Criteria` section of the core guideline for how that wiring works).
- `Repositories/{Name}Criteria.php`: add typed public properties for any filters beyond the inherited `search`, `sort_column`, `sort_order`, `per_page`, `limit`.
- `Exceptions/{Name}.php`: adjust the message/code at the throw site; the generated default HTTP code came from `--code=`.

## 5. Checking what already exists

```bash
php artisan domain:list
```

Prints each domain under `app/Domains` and how many files live in each subdirectory — useful before deciding whether to scaffold a new domain or extend an existing one.

## 6. Customizing generated code

If the generated stubs don't match the app's conventions, publish and edit them once, and every future `domain:make-*` call in the app will use the edited version:

```bash
php artisan vendor:publish --provider="HarryM\DomainSupport\DomainSupportServiceProvider" --tag="domain-support.stubs"
```

This copies the package's `stubs/*.stub` files into `stubs/domain-support/` in the app; edit them directly rather than patching the generated output by hand each time.
