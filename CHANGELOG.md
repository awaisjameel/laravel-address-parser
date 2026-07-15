# Changelog

All notable changes to `laravel-address-parser` are documented in this file. This project adheres to [Semantic Versioning](https://semver.org/).

## [1.1.0] - 2026-07-15

### Added

-   Laravel 13 support (`illuminate/contracts` `^13.0`, tested against `orchestra/testbench` `^11.0`).
-   PHP 8.5 added to the CI test matrix.

### Fixed

-   Package now genuinely runs on PHP 8.2/8.3: replaced the PHP 8.4-only `mb_trim()`/`mb_rtrim()` calls with `trim()`/`rtrim()`. Previously the code fataled on PHP < 8.4 unless `symfony/polyfill-mbstring` happened to be installed.
-   Corrected the PHP version constraint from the meaningless `>=7.4 || >=8.2` to `^8.2`, and declared the `ext-mbstring` requirement explicitly.
-   Cities whose names begin with a unit-indicator prefix (e.g. `Florence` → `FL`, `Sterling` → `STE`, `Lots` → `LOT`) no longer fail with "City cannot be empty after parsing unit"; unit words must now match exactly.
-   `#`-style units embedded in a comma-separated address line (e.g. `77 Broadway St #12, Gotham, NJ 07001`) are now correctly split into `address2` (the previous regex could never match `#`).
-   Multibyte street names (e.g. `123 Peña Blvd Denver CO 80249`) no longer produce corrupted city/state output caused by mixing byte offsets with character-based `mb_substr()`.
-   Removed the `version` field from `composer.json` (release versions come from git tags).
-   Removed the nonexistent `database` path from the PHPStan configuration and the unused `database/factories` autoload mapping.

## [1.0.0] - 2025-11-05

### Added

-   Initial stable release.
-   Core parser: `LaravelAddressParser::parseAddressString()` for single-line US addresses.
-   County-aware parser: `parseAddressStringWithCounty()`.
-   Validation helpers: `isValidZipCode()`, `isValidState()`, `getValidStates()`.
-   Address formatter: `formatAddress()`.
-   Exception handling via `AddressParsingException`.
-   Street suffix + unit indicator heuristic lists.
-   Automatic normalization (whitespace collapse, punctuation trimming, period removal after known abbreviations).
-   Facade alias `LaravelAddressParser`.
-   Config file stub published via `laravel-address-parser-config` tag for future extensibility.

### Notes

-   This is a format parser, not an address existence validator (no USPS/CASS integration yet).
-   Future roadmap will introduce configurable suffixes/unit indicators and integration hooks.

[1.1.0]: https://github.com/awaisjameel/laravel-address-parser/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/awaisjameel/laravel-address-parser/releases/tag/v1.0.0
