# PHP UUID Tools

[![Tests](https://github.com/philiprehberger/php-uuid-tools/actions/workflows/tests.yml/badge.svg)](https://github.com/philiprehberger/php-uuid-tools/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/philiprehberger/php-uuid-tools.svg)](https://packagist.org/packages/philiprehberger/php-uuid-tools)
[![License](https://img.shields.io/github/license/philiprehberger/php-uuid-tools)](LICENSE)

UUID v4 and v7 generation, validation, and ordered UUIDs for database indexing.

## Requirements

- PHP 8.2+

## Installation

```bash
composer require philiprehberger/php-uuid-tools
```

## Usage

### Generate UUID v4

```php
use PhilipRehberger\UuidTools\Uuid;

$uuid = Uuid::v4();
// "f47ac10b-58cc-4372-a567-0e02b2c3d479"
```

### Generate UUID v7

Time-ordered UUIDs with millisecond precision, ideal for database primary keys:

```php
$uuid = Uuid::v7();
// "018e4f6c-1a2b-7000-8000-1234567890ab"
```

### Validate a UUID

```php
Uuid::isValid('550e8400-e29b-41d4-a716-446655440000'); // true
Uuid::isValid('not-a-uuid');                            // false
```

### Extract Version

```php
Uuid::version('550e8400-e29b-41d4-a716-446655440000'); // 4
Uuid::version('invalid');                               // null
```

### Binary Conversion

Convert between UUID strings and 16-byte binary for compact storage:

```php
$bytes = Uuid::toBytes('550e8400-e29b-41d4-a716-446655440000');
// 16-byte binary string

$uuid = Uuid::fromBytes($bytes);
// "550e8400-e29b-41d4-a716-446655440000"
```

### Ordered UUIDs

Reorder UUID fields for optimal database index performance. Puts the most-significant time bits first so UUIDs sort chronologically:

```php
$uuid = Uuid::v7();
$ordered = Uuid::toOrdered($uuid);

// Store $ordered in the database for better index locality

$original = Uuid::fromOrdered($ordered);
// Recovers the original UUID
```

### Batch Generation

Generate multiple UUIDs at once:

```php
$uuids = Uuid::batch(5);
// [
//     "f47ac10b-58cc-4372-a567-0e02b2c3d479",
//     "6ba7b810-9dad-41d1-80b4-00c04fd430c8",
//     ...
// ]

$v7Uuids = Uuid::batch(3, 7);
// Three time-ordered v7 UUIDs
```

### Comparison

```php
$a = Uuid::v4();
$b = Uuid::v4();

Uuid::equals($a, $a);        // true
Uuid::equals($a, $b);        // false

Uuid::compareTo($a, $b);     // -1, 0, or 1
```

### Nil UUID

```php
$nil = Uuid::nil();
// "00000000-0000-0000-0000-000000000000"
```

## API

| Method | Description |
|---|---|
| `Uuid::v4(): string` | Generate a random UUID v4 |
| `Uuid::v7(): string` | Generate a time-ordered UUID v7 |
| `Uuid::isValid(string $uuid): bool` | Validate a UUID string (any version) |
| `Uuid::version(string $uuid): ?int` | Extract the version number (null if invalid) |
| `Uuid::toBytes(string $uuid): string` | Convert UUID to 16-byte binary |
| `Uuid::fromBytes(string $bytes): string` | Convert 16-byte binary to UUID string |
| `Uuid::toOrdered(string $uuid): string` | Reorder UUID for database index performance |
| `Uuid::fromOrdered(string $ordered): string` | Reverse an ordered UUID to standard format |
| `Uuid::equals(string $a, string $b): bool` | Case-insensitive UUID equality check |
| `Uuid::compareTo(string $a, string $b): int` | Lexicographic comparison (-1, 0, 1) for sorting |
| `Uuid::batch(int $count, int $version = 4): array` | Generate multiple UUIDs at once |
| `Uuid::nil(): string` | Return the nil UUID (all zeros) |

## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

## License

MIT
