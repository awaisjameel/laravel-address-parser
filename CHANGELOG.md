# Changelog

All notable changes to `laravel-address-parser` are documented in this file. This project adheres to [Semantic Versioning](https://semver.org/).

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

[1.0.0]: https://github.com/awaisjameel/laravel-address-parser/releases/tag/v1.0.0
