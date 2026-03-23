# Classic to Gutenberg Addons

[![PHP CI](https://github.com/apermo/classic-to-gutenberg-addons/actions/workflows/ci.yml/badge.svg)](https://github.com/apermo/classic-to-gutenberg-addons/actions/workflows/ci.yml)
[![License: GPL v2+](https://img.shields.io/badge/License-GPLv2+-blue.svg)](LICENSE)

Niche converters for [Classic to Gutenberg](https://github.com/apermo/classic-to-gutenberg): page builders, third-party
plugins, and other specialized HTML patterns that don't belong in the core plugin.

## Requirements

- PHP 8.2+
- WordPress 6.2+
- [apermo/classic-to-gutenberg](https://github.com/apermo/classic-to-gutenberg) ^0.4

## Installation

```bash
composer require apermo/classic-to-gutenberg-addons
```

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
