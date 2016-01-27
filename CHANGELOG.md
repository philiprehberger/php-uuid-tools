# Changelog

All notable changes to this project will be documented in this file.

## [1.0.2] - 2026-03-17

### Changed
- Standardized package metadata, README structure, and CI workflow per package guide

## [1.0.1] - 2026-03-16

### Changed
- Standardize composer.json: add type, homepage, scripts

## [1.0.0] - 2026-03-13

### Added

- UUID v4 generation using cryptographically secure random bytes
- UUID v7 generation with millisecond-precision timestamps
- UUID validation for any version
- Version extraction from UUID strings
- Binary conversion (`toBytes` / `fromBytes`)
- Ordered UUID conversion for optimal database indexing (`toOrdered` / `fromOrdered`)
- Nil UUID helper
