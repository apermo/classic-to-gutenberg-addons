# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

WordPress plugin that converts [WPBakery Page Builder](https://wpbakery.com/) (formerly Visual Composer)
shortcodes to native Gutenberg blocks. Extends
[Classic to Gutenberg](https://github.com/apermo/classic-to-gutenberg) (`apermo/classic-to-gutenberg`) by hooking into
its `pre_convert`/`post_convert` filter pipeline.

**PHP 8.2+ minimum.** **WordPress 6.2+.** Strict types everywhere (`declare(strict_types=1)`).

### Relationship to Classic to Gutenberg

The core plugin (lives at `/Users/cd/repos/apermo/classic-to-gutenberg`) handles generic HTML-to-block conversion. It
exposes `classic_to_gutenberg_pre_convert` and `classic_to_gutenberg_post_convert` filters. This plugin hooks both:

- **pre_convert**: Finds `[vc_row]...[/vc_row]` blocks before `wpautop` fragments them, converts to Gutenberg blocks,
  replaces with placeholder comments
- **post_convert**: Swaps placeholders (wrapped in `<!-- wp:html -->` by the pipeline) back with converted blocks

This approach piggybacks on the core's entire infrastructure: `ContentConverter`, `MigrationRunner` (post locking,
revisions, rollback), CLI commands, and admin UI.

### Conversion approach

WPBakery shortcodes → Gutenberg blocks:

- `[vc_row]` with single full-width column → unwrapped inner content
- `[vc_row]` with multiple columns → `core/columns` + `core/column` blocks
- `[vc_column_text]` → inner HTML converted via core's `ContentConverter`
- `[vc_row_inner]` / `[vc_column_inner]` → nested columns (recursive)
- Unknown shortcodes → `core/shortcode` block (fallback)

Key design decisions:

- **Re-entrancy guard**: Inner `ContentConverter` also fires `pre_convert`/`post_convert` filters; depth counter prevents
  nested calls from resetting state
- **Placeholder mechanism**: Converted blocks survive the pipeline as HTML comments, swapped back in `post_convert`
- **Element handler registry**: Each WPBakery shortcode type is a standalone handler class implementing
  `VcElementHandlerInterface`, making new elements trivial to add

## Architecture

- `plugin.php` — Main plugin entry point
- `src/Plugin.php` — Plugin bootstrap (hooks into `plugins_loaded`)
- `src/WPBakery/WPBakery.php` — Registration: hooks pre/post_convert, wires handlers
- `src/WPBakery/Converter.php` — Pre/post_convert orchestrator with placeholder mechanism
- `src/WPBakery/RowConverter.php` — Converts `[vc_row]` to columns/content blocks
- `src/WPBakery/ShortcodeParser.php` — Attribute parsing, content extraction, width mapping
- `src/WPBakery/ElementHandler/` — Individual shortcode type handlers
- `tests/` — PHPUnit tests (Unit + Integration)
- `tests/fixtures/` — Input/expected output pairs for integration tests
- `uninstall.php` — Cleanup on plugin deletion

### Key conventions

- PSR-4 autoloading under `src/` (namespace: `Apermo\WPBakeryToGutenberg`)
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
- Custom fragment `28-link-core-plugin.sh` symlinks the core plugin from `vendor/` into WordPress
- Custom fragment `31-activate-core-plugin.sh` activates the core plugin

## Git Workflow

**Never push directly to main.** All changes go through feature branches and pull requests.

Branch naming: `<type>/<short-description>` (e.g. `feat/image-handler`, `fix/column-width`).

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
