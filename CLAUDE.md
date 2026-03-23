# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

WordPress plugin providing niche converters for
[Classic to Gutenberg](https://github.com/apermo/classic-to-gutenberg) (`apermo/classic-to-gutenberg`): page builders,
third-party plugins, and other specialized HTML patterns that don't belong in the core plugin.

Origin: [apermo/classic-to-gutenberg#18](https://github.com/apermo/classic-to-gutenberg/issues/18).

**PHP 8.2+ minimum.** **WordPress 6.2+.** Strict types everywhere (`declare(strict_types=1)`).

### Relationship to the core plugin

The core plugin (`apermo/classic-to-gutenberg`, lives at `/Users/cd/repos/apermo/classic-to-gutenberg`) handles generic
HTML-to-block conversion (paragraphs, headings, images, lists, tables, shortcodes, etc.). It exposes a
`classic_to_gutenberg_converters` filter that passes a `BlockConverterFactory` instance. This addons plugin hooks into
that filter to register additional converters for page-builder and third-party-plugin markup.

Key core classes to know:

- `BlockConverterInterface` — interface every converter implements (`get_supported_tags`, `can_convert`, `convert`)
- `AbstractBlockConverter` — optional base class with serialization helpers
- `BlockConverterFactory` — LIFO registry; `register()` adds a converter, `get_converter()` resolves by tag + content
- The factory checks converters in reverse registration order; first `can_convert() === true` wins

### Design principles

- **Opt-in per page builder** — users register only converters they need
- **Filters only** — no custom directory loader, no mu-plugins pattern, no admin UI or CLI
- **Own release cycle** — can move faster than core
- **Own test fixtures** — real-world page builder markup
- **Broken markup policy inherited from core** — shit in, shit out; don't repair broken HTML

## Architecture

- `plugin.php` — Main plugin entry point
- `src/Plugin.php` — Plugin bootstrap (hooks into `plugins_loaded`, registers converters via filter)
- `src/` — PSR-4 root (namespace: `Apermo\ClassicToGutenbergAddons`)
- `tests/` — PHPUnit tests (Unit + Integration)
- `uninstall.php` — Cleanup on plugin deletion

### Converter structure (planned)

Each page builder / third-party plugin gets its own namespace directory under `src/`:

```
src/
├── Plugin.php
├── WPBakery/
│   ├── WPBakery.php              ← convenience registration class
│   ├── RowConverter.php
│   ├── ColumnConverter.php
│   └── ColumnTextConverter.php
├── Elementor/
│   └── ...
└── TablePress/
    └── ...
```

Registration pattern:

```php
add_filter( 'classic_to_gutenberg_converters', function ( BlockConverterFactory $factory ): BlockConverterFactory {
    WPBakery::register( $factory );
    return $factory;
} );
```

### Key conventions

- PSR-4 autoloading under `src/`
- Coding standards: `apermo/apermo-coding-standards` (PHPCS)
- Static analysis: `apermo/phpstan-wordpress-rules` + `szepeviktor/phpstan-wordpress`
- Testing: PHPUnit + Brain Monkey + Yoast PHPUnit Polyfills
- Test suites: `tests/Unit/` and `tests/Integration/`

## Commands

```bash
composer cs              # Run PHPCS
composer cs:fix          # Fix PHPCS violations
composer analyse         # Run PHPStan
composer test            # Run all tests
composer test:unit       # Run unit tests only
composer test:integration # Run integration tests only
npm run test:e2e         # Run Playwright E2E tests
npm run test:e2e:ui      # Run E2E tests with UI
```

## Local Development (DDEV)

```bash
ddev start && ddev orchestrate   # Full WordPress environment
```

- Uses `apermo/ddev-orchestrate` addon
- Project type is `php` (not `wordpress`), so WP-CLI uses a custom `ddev wp` command wrapper
- Both this plugin and the core plugin need to be symlinked into `wp-content/plugins/`

## Git Workflow

**Never push directly to main.** All changes go through feature branches and pull requests.

Branch naming: `<type>/<short-description>` (e.g. `feat/wpbakery-converters`, `fix/column-parsing`).

## Git Hooks

Pre-commit hook runs PHPCS and PHPStan on staged files. Enable with:

```bash
git config core.hooksPath .githooks
```

## CI (GitHub Actions)

- `ci.yml` — PHPCS + PHPStan + PHPUnit across PHP 8.2, 8.3, 8.4
- `integration.yml` — WP integration tests (real WP + MySQL, multisite matrix)
- `e2e.yml` — Playwright E2E tests against running WordPress
- `wp-beta.yml` — Nightly WP beta/RC compatibility check
- `release.yml` — CHANGELOG-driven releases
- `pr-validation.yml` — conventional commit and changelog checks

## Template Sync

```bash
git remote add template https://github.com/apermo/template-wordpress.git
git fetch template
git checkout -b chore/sync-template
git merge template/main --allow-unrelated-histories
```
