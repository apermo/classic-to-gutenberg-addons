# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2026-03-24

### Added

- WPBakery row/column conversion (`[vc_row]`, `[vc_column]` → `core/columns` + `core/column`)
- Single full-width column unwrapping
- Nested row support (`[vc_row_inner]` / `[vc_column_inner]`)
- Element handler registry (`VcElementHandlerInterface`)
- Handlers: `[vc_column_text]`, `[vc_separator]`, `[vc_empty_space]`, `[vc_single_image]`, `[vc_btn]`, `[vc_raw_html]`
- Fractional width → percentage conversion
- Pre/post_convert pipeline integration with classic-to-gutenberg
- Re-entrancy guard for nested ContentConverter calls
- Unit tests (45 tests) and fixture-based integration tests
- DDEV orchestrate setup for local development

[Unreleased]: https://github.com/apermo/wpbakery-to-gutenberg/compare/0.1.0...HEAD
[0.1.0]: https://github.com/apermo/wpbakery-to-gutenberg/releases/tag/0.1.0
