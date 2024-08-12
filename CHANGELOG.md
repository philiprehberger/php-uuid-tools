# Changelog

All notable changes to `php-uuid-tools` will be documented in this file.

## [Unreleased]

## [1.2.0] - 2026-03-31

### Added
- UUID v5 generation via `Uuid::v5()` with standard RFC 4122 namespace constants
- ULID support via `Uuid::ulid()`, `Ulid::generate()`, `Ulid::toUuid()`, and `Ulid::timestamp()`
- Short ID encoding via `Uuid::toShortId()` and `Uuid::fromShortId()` using Base62

## [1.1.1] - 2026-03-31

### Changed
- Standardize README to 3-badge format with emoji Support section
- Update CI checkout action to v5 for Node.js 24 compatibility
- Add GitHub issue templates, dependabot config, and PR template

## [1.1.0] - 2026-03-22

### Added
- `equals()` method for UUID comparison
- `compareTo()` method for UUID sorting
- `batch()` static method for generating multiple UUIDs at once

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
