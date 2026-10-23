# AGENTS.md

Rules for any coding agent working in this repository.

## What this is

`mwpd/content-freshness` is a WordPress plugin: it warns readers when a post
is out of date, shows editors which posts are going stale, and emails them a
weekly digest. It is also the worked example of the
[`mwpd/basic-scaffold`](https://github.com/mwpd/basic-scaffold) plugin
template, and its code is quoted to show how the scaffold's architecture keeps
complexity linear. So every file is teaching material: it has to be correct,
tested and readable on its own.

| Path | What it is |
| --- | --- |
| `content-freshness.php` | Bootstrap: plugin header, autoloading, lifecycle bridges, kicks off the composition root. |
| `src/ContentFreshnessPlugin.php` | Composition root: services, injector bindings, shared instances, prefixes. |
| `src/Freshness/` | The domain: `FreshnessPolicy` and its option-backed implementation, the `MaxAge` and `PostAge` value objects, the `Clock`. |
| `src/Notice/`, `src/PostsList/`, `src/Settings/`, `src/Digest/` | The four features, one service each, with their WordPress adapters. |
| `src/Infrastructure/` | The scaffold's infrastructure: service container, injector, views, autoloader, capability interfaces. |
| `src/Exception/` | The plugin's exception types. |
| `views/` | Templates the services render. |
| `tests/php/Unit`, `tests/php/Integration` | PHPUnit suites. Both use Brain Monkey; neither needs a WordPress install. |
| `tests/php/Fixture/` | Test doubles, stand-ins for WordPress classes, view fixtures. |
| `tests/php/Architecture/`, `tests/php/PHPStan/` | Architecture rules: the `architecture` suite, PHPStan rules, their tests and fixtures. |
| `tests/e2e/` | End-to-end tests in a real WordPress, through `@wordpress/env`. |

## Commands

```sh
composer install
composer test               # unit + integration + architecture
composer lint               # phpcs, phpstan (level max), psalm (level 2), PHPStan rule tests
composer phpcs:fix          # auto-fix coding standards
composer check:autoloader   # every declaration through the bundled autoloader alone
composer api:snapshot       # regenerate the infrastructure API listing after review
vendor/bin/rector --dry-run # rule sets up to PHP 7.4, code quality, style

npm ci
npm run e2e                 # WordPress in Docker: activation, notice, column, setting, digest, uninstall
```

The architecture rules are part of both: PHPStan rules in `composer lint`, the
`architecture` test suite in `composer test`. Every failure says what to do
instead; [docs/architecture-rules.md](docs/architecture-rules.md) lists the
rules and how to add one.

Psalm 5 runs on PHP 8.3 at most, and `composer.lock` was resolved for PHP 8.
CI (`.github/workflows/ci.yml`) is the reference environment: lint on 8.3,
tests on 7.4 and 8.3, the bundled autoloader on 7.4 without `vendor/`, and the
end-to-end tests on WordPress 6.0 and the latest release. Without a matching
local PHP, run the same steps in a `php:8.3-cli` or `php:7.4-cli` container
with Composer added.

## Design principles

Every technical decision is measured against
[docs/design-principles.md](docs/design-principles.md). Read it before
choosing between approaches.

**Look up the need before the workaround.** When you are about to reach for
something the principles rule out (a service locator, a singleton, a global,
an `is_admin()` check around hooks), or when an architecture rule fails, find
what you were trying to do in [docs/patterns.md](docs/patterns.md). Each
pattern shows the plugin's way, with code that passes the checks.

**The core idea: complexity grows linearly.** Every concern is its own
service, and each service decides in isolation where and when it acts in the
WordPress lifecycle, by implementing small capability interfaces
(`Conditional`, `Delayed`, `HasDependencies`, `Registerable`, `Activateable`,
`Deactivateable`, `Uninstallable`). The infrastructure works out when to
trigger what. New logic is one more service, never one more condition threaded
through the others. A lifecycle point no interface covers gets a new
interface, not a conditional inside a service.

The principles, in short:

1. **Runs on PHP and WordPress alone.** Composer is a development tool, never
   a runtime requirement; the fallback autoloader keeps the plugin working
   without `vendor/`.
2. **Self-contained infrastructure, replaceable by design.** Simple in-house
   implementations behind interfaces, not third-party packages.
3. **Defaults out of the box (poka-yoke).** Optional collaborators fall back
   to a built-in implementation; a custom one is simply passed in.
4. **SOLID at the top, WordPress at the edges.** Strict SOLID for the
   high-level design; implementation details may use WordPress directly.
5. **One composition root builds the object graph.** Deferred only by lazy or
   on-demand instantiation through the injector. `new` is only for value
   objects and exceptions, inside factories, and for poka-yoke defaults.
6. **Final by default.** Only classes designed as a base are open.
7. **Show the alternative to WordPress anti-patterns.** No hooks in
   constructors, no singletons, no `new` or globals for collaborators, no side
   effects on include.
8. **Types name concepts, not just data.**
9. **Never trust what WordPress hands over.** Normalize at the boundary into
   internal types; escape output for the context it lands in.
10. **Errors are exceptions.** SPL-based, one marker interface, named
    constructors. They never reach the end user: loud with debug mode on,
    graceful degradation with it off.
11. **Lifecycle steps are idempotent.**
12. **Extension points run at a hook, never at include time.**
13. **One plugin, to arbitrary complexity.** No cross-plugin coordination;
    everything public carries the plugin's prefix.
14. **The code teaches.** Comments explain why; every service and view is
    correct and tested.

A change that contradicts a principle needs a maintainer ruling on its issue
before code.

## Hard rules

- **`content-freshness.php` is the only file with side effects.** Everything
  under `src/` is autoloadable declarations: no code that runs on include.
- **PHP 7.4 and WordPress 6.0 are the floor.** No syntax or functions newer
  than PHP 7.4 in `src/`, `tests/` or `views/`; `phpcs` enforces it through
  PHPCompatibility. No WordPress function newer than 6.0 at runtime.
- **Every PHP class file declares `strict_types=1`**: the bootstrap, `src/`
  and `tests/php`. View templates (`views/`, `tests/php/Fixture/views/`) do not.
- **No runtime Composer dependencies** (principle 1). `require` holds nothing
  but PHP; class loading must work both with and without `vendor/`.
- **All strings are translatable** with the `content-freshness` text domain,
  and **all output is escaped** for the context it lands in.
- **Infrastructure changes are API changes.** `src/Infrastructure/` is the
  scaffold's code. A change there updates the reviewed listing with
  `composer api:snapshot`, and is a candidate to send upstream to
  `mwpd/basic-scaffold`. A change that removes or reshapes existing API needs
  a maintainer ruling first.
- **Do not weaken the gates to get green.** No lowered PHPStan/Psalm levels,
  new baselines, broad ignores, or deleted tests. Fix the code or say why on
  the issue.
- **Verify technical claims against the source**, such as the installed package,
  upstream docs or the registry, before building on them. Do not carry a version
  number or an API shape from memory.

## Working on changes

- Before writing code against an issue, read it, and **verify that its
  `file:line` citations still point at what it claims**. The documentation's
  citations are checked by `architecture.citationsResolve`; when you move code,
  update the lines it cites.
- **Work found mid-session gets filed, not fixed and not forgotten.** A gap in
  the infrastructure or the rules is filed on `mwpd/basic-scaffold` when it
  applies to every plugin built from the scaffold.
- Commits and PR titles are plain imperative sentences, matching the existing
  log ("Add the outdated post notice").
