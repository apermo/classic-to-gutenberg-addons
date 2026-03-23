# template-wordpress

[![PHP CI](https://github.com/apermo/template-wordpress/actions/workflows/ci.yml/badge.svg)](https://github.com/apermo/template-wordpress/actions/workflows/ci.yml)
[![License: GPL v2+](https://img.shields.io/badge/License-GPLv2+-blue.svg)](LICENSE)

GitHub template repository for bootstrapping WordPress plugins and themes. Ships both plugin and theme scaffolding; a `setup.sh` script lets developers choose their mode and configures the project accordingly.

## Requirements

- PHP 8.1+
- Composer
- [DDEV](https://ddev.readthedocs.io/) (for local development)

## Installation

1. [Create a new repository from this template](https://github.com/apermo/template-wordpress/generate)
2. Clone your new repository
3. Run the setup script:

```bash
bash setup.sh
```

The script prompts for:
- **Slug** (kebab-case, e.g. `my-plugin`)
- **Namespace** (e.g. `Apermo\MyPlugin`)
- **Composer package name**
- **Mode** (`plugin` or `theme`)

It replaces all placeholders, removes irrelevant mode files, configures DDEV, and optionally sets up GitHub labels and branch protection.

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

Uses [ddev-orchestrate](https://github.com/apermo/ddev-orchestrate) to download WordPress, create `wp-config.php`, install, and activate the plugin/theme.

### Git Hooks

Enable the pre-commit hook (PHPCS + PHPStan on staged files):

```bash
git config core.hooksPath .githooks
```

## Template Sync

To pull upstream template changes into a derived project:

```bash
git remote add template https://github.com/apermo/template-wordpress.git
git fetch template
git checkout -b chore/sync-template
git merge template/main --allow-unrelated-histories
```

## License

[GPL-2.0-or-later](LICENSE)
