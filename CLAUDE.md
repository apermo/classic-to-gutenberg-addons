# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

GitHub template repository for bootstrapping WordPress plugins and themes. Ships both plugin and theme scaffolding; a `setup.sh` script lets developers choose their mode and configures the project accordingly.

**PHP 8.1+ minimum.** Strict types everywhere (`declare(strict_types=1)`).

## Architecture

### Dual-mode template (plugin + theme)

Both modes coexist in the repo. The `setup.sh` script (see #10) removes the irrelevant set after the developer picks a mode.

**Plugin mode files:** `plugin.php` (main file), `src/Plugin.php`, `uninstall.php`
**Theme mode files:** `style.css`, `functions.php`, `src/Theme.php`, `templates/`, `parts/`, `assets/`
**Shared:** `src/` (PSR-4 root), `tests/`, `composer.json`, CI config, DDEV config

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
- Plugin mode: bind-mounts repo into `wp-content/plugins/`
- Theme mode: bind-mounts repo into `wp-content/themes/`

## Git Hooks

Pre-commit hook runs PHPCS and PHPStan on staged files. Enable with:

```bash
git config core.hooksPath .githooks
```

## CI (GitHub Actions)

- `ci.yml` — PHPCS + PHPStan + PHPUnit across PHP 8.1, 8.2, 8.3, 8.4
- `integration.yml` — WP integration tests (real WP + MySQL, multisite matrix)
- `e2e.yml` — Playwright E2E tests against running WordPress
- `wp-beta.yml` — Nightly WP beta/RC compatibility check
- `release.yml` — CHANGELOG-driven releases
- `pr-validation.yml` — conventional commit and changelog checks

### Integration test environment

Integration tests run against a real WordPress instance. The bootstrap auto-detects
`vendor/wp-phpunit/wp-phpunit` when `WP_TESTS_DIR` is unset. For local development:

```bash
composer require --dev wp-phpunit/wp-phpunit
cp wp-tests-config.php.dist wp-tests-config.php  # edit DB credentials
composer test:integration
```

You can also set `WP_TESTS_DIR` explicitly:

```bash
WP_TESTS_DIR=/tmp/wordpress-tests-lib WP_MULTISITE=1 composer test:integration
```

When neither `WP_TESTS_DIR` nor `vendor/wp-phpunit/wp-phpunit` exist, the bootstrap
skips WP loading — unit tests work unchanged.

### E2E test environment

E2E tests use Playwright against a running WordPress instance (DDEV locally, PHP built-in server in CI):

```bash
npm ci
npx playwright install --with-deps chromium
npm run test:e2e
```

The `WP_BASE_URL` env var overrides the default DDEV site URL. Authentication
is handled by `e2e/auth.setup.js` which stores state in `.auth/admin.json`.

## Template Sync (for derived projects)

```bash
git remote add template https://github.com/apermo/template-wordpress.git
git fetch template
git checkout -b chore/sync-template
git merge template/main --allow-unrelated-histories
```

## Placeholder conventions

The setup script replaces these across all files:
- `plugin-name` → slug (kebab-case)
- `Plugin_Name` → PascalCase
- `PLUGIN_NAME` → UPPER_SNAKE_CASE
- `plugin_name` → snake_case
- Placeholder namespace → chosen namespace
