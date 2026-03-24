# WPBakery to Gutenberg

[![PHP CI](https://github.com/apermo/wpbakery-to-gutenberg/actions/workflows/ci.yml/badge.svg)](https://github.com/apermo/wpbakery-to-gutenberg/actions/workflows/ci.yml)
[![License: GPL v2+](https://img.shields.io/badge/License-GPLv2+-blue.svg)](LICENSE)

Convert [WPBakery Page Builder](https://wpbakery.com/) (formerly Visual Composer) content to native Gutenberg blocks.
Extends [Classic to Gutenberg](https://github.com/apermo/classic-to-gutenberg) by hooking into its conversion pipeline.

## Supported Elements

| WPBakery Shortcode | Gutenberg Block |
|---|---|
| `[vc_row]` / `[vc_row_inner]` | `core/columns` (multi-column) or unwrapped (single column) |
| `[vc_column]` / `[vc_column_inner]` | `core/column` with percentage width |
| `[vc_column_text]` | Inner HTML converted to standard blocks |

## Requirements

- PHP 8.2+
- WordPress 6.5+
- [apermo/classic-to-gutenberg](https://github.com/apermo/classic-to-gutenberg) ^0.5

## Installation

```bash
composer require apermo/wpbakery-to-gutenberg
```

Activate both plugins. WPBakery shortcodes are automatically converted when using Classic to Gutenberg's
CLI commands or admin UI.

## Development

```bash
composer install
composer cs              # Run PHPCS
composer cs:fix          # Fix PHPCS violations
composer analyse         # Run PHPStan
composer test            # Run all tests
composer test:unit       # Run unit tests only
composer test:integration # Run integration tests only
```

### Local WordPress Environment

```bash
ddev start && ddev orchestrate
```

### Git Hooks

Enable the pre-commit hook (PHPCS + PHPStan on staged files):

```bash
git config core.hooksPath .githooks
```

## License

[GPL-2.0-or-later](LICENSE)
