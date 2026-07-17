# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Releases before 1.6.0 predate this changelog. See the
[GitHub releases](https://github.com/botnet-dobbs/laravel-luminous/releases) for older history.

## [1.7.0] - 2026-07-17

### Changed

- `Shape::min()` and `Shape::max()` accept floats, and `TypeMapper` keeps float parameters from `min:`, `max:`, `size:`, and `between:` rules on numeric fields, matching how Laravel compares them. A `min:0.01` rule now documents `minimum: 0.01` instead of `minimum: 0`. String lengths and array item counts are still whole numbers.

## [1.6.0] - 2026-07-11

### Added

- `GeneratorFactory` as the single place that wires extractors into `OpenApiGenerator`. The service provider and the test suite now build the generator the same way.
- `OpenApiGeneratorContract` so the generator can be swapped or mocked. Commands and the HTTP controller type-hint the contract.
- Versioned spec cache keys. The configured key is now a prefix; Luminous appends a short hash of the package version and config (UI settings excluded), so upgrades and config changes never serve a stale cached spec. Old entries expire with the TTL.
- CI quality job running Pint and PHPStan (level 5), with matching composer scripts `analyse` and `lint`.
- MIT `LICENSE` file and this changelog.

### Changed
- The deployment guide covers using exported specs with OpenAPI linters (Redocly, Spectral) and SDK generators (openapi-generator, Fern). The README FAQ links to that section.
- Laravel 11 stays supported in `composer.json` but is no longer tested in CI: Composer refuses to install the affected `laravel/framework` 11.x releases because of published security advisories.

### Fixed

- Enum case descriptions (`x-enum-descriptions`) now work. Doc-commented cases previously threw a `TypeError` (enum case object used as an array key), and the plain doc-comment fallback captured a leading `* ` in the description text.
- The docs-route spec cache lock is only used when the cache store supports atomic locks. Stores without lock support previously caused a `BadMethodCallException` on a cold cache.
- Config comments and deployment docs now document the `|` delimiter for `LUMINOUS_MIDDLEWARE`. Commas break middleware parameters such as `throttle:60,1`.
- `luminous:generate --validate` documentation and CLI help now describe the basic structural checks that actually run, and link to real OpenAPI linters for a full check.

[Unreleased]: https://github.com/botnet-dobbs/laravel-luminous/compare/v1.6.0...HEAD
[1.6.0]: https://github.com/botnet-dobbs/laravel-luminous/compare/v1.5.3...v1.6.0
