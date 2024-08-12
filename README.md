# PHP UUID Tools

[![Tests](https://github.com/philiprehberger/php-uuid-tools/actions/workflows/tests.yml/badge.svg)](https://github.com/philiprehberger/php-uuid-tools/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/philiprehberger/php-uuid-tools.svg)](https://packagist.org/packages/philiprehberger/php-uuid-tools)
[![Last updated](https://img.shields.io/github/last-commit/philiprehberger/php-uuid-tools)](https://github.com/philiprehberger/php-uuid-tools/commits/main)

UUID v4, v5, and v7 generation, ULID support, short ID encoding, and ordered UUIDs for database indexing.

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

### Generate UUID v5

Deterministic namespace-based UUIDs using SHA-1 hashing:

```php
use PhilipRehberger\UuidTools\Uuid;

$uuid = Uuid::v5(Uuid::NAMESPACE_DNS, 'example.com');
// "cfbff0d1-9375-5685-968c-48ce8b15ae17"

// Same inputs always produce the same UUID
$uuid2 = Uuid::v5(Uuid::NAMESPACE_DNS, 'example.com');
// $uuid === $uuid2

// Available namespace constants:
// Uuid::NAMESPACE_DNS, Uuid::NAMESPACE_URL,
// Uuid::NAMESPACE_OID, Uuid::NAMESPACE_X500
```

### ULID

Generate ULIDs (Universally Unique Lexicographically Sortable Identifiers):

```php
use PhilipRehberger\UuidTools\Ulid;

$ulid = Ulid::generate();
// "01ARZ3NDEKTSV4RRFFQ69G5FAV"

Ulid::isValid($ulid); // true

// Convert between ULID and UUID
$uuid = Ulid::toUuid($ulid);
$ulid = Ulid::fromUuid($uuid);

// Extract timestamp (milliseconds since Unix epoch)
$ms = Ulid::timestamp($ulid);

// Convenience methods on Uuid class
$ulid = Uuid::ulid();
Uuid::isValidUlid($ulid); // true
```

### Short IDs

Encode UUIDs as compact Base62 strings (~22 characters):

```php
use PhilipRehberger\UuidTools\ShortId;

$shortId = ShortId::encode('550e8400-e29b-41d4-a716-446655440000');
// "2D5MNbitT4FNsgGOLfVm6q"

$uuid = ShortId::decode($shortId);
// "550e8400-e29b-41d4-a716-446655440000"

// Convenience methods on Uuid class
$shortId = Uuid::toShortId($uuid);
$uuid = Uuid::fromShortId($shortId);
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
| `Uuid::v5(string $namespace, string $name): string` | Generate a deterministic UUID v5 (SHA-1) |
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
| `Uuid::ulid(): string` | Generate a ULID |
| `Uuid::isValidUlid(string $ulid): bool` | Validate a ULID string |
| `Uuid::toShortId(string $uuid): string` | Encode UUID as Base62 short ID |
| `Uuid::fromShortId(string $shortId): string` | Decode Base62 short ID to UUID |
| `Ulid::generate(): string` | Generate a new ULID |
| `Ulid::isValid(string $ulid): bool` | Validate a ULID string |
| `Ulid::toUuid(string $ulid): string` | Convert ULID to UUID format |
| `Ulid::fromUuid(string $uuid): string` | Convert UUID to ULID format |
| `Ulid::timestamp(string $ulid): int` | Extract Unix timestamp (ms) from ULID |
| `ShortId::encode(string $uuid): string` | Encode UUID as Base62 short ID |
| `ShortId::decode(string $shortId): string` | Decode Base62 short ID to UUID |

## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

## Support

If you find this project useful:

⭐ [Star the repo](https://github.com/philiprehberger/php-uuid-tools)

🐛 [Report issues](https://github.com/philiprehberger/php-uuid-tools/issues?q=is%3Aissue+is%3Aopen+label%3Abug)

💡 [Suggest features](https://github.com/philiprehberger/php-uuid-tools/issues?q=is%3Aissue+is%3Aopen+label%3Aenhancement)

❤️ [Sponsor development](https://github.com/sponsors/philiprehberger)

🌐 [All Open Source Projects](https://philiprehberger.com/open-source-packages)

💻 [GitHub Profile](https://github.com/philiprehberger)

🔗 [LinkedIn Profile](https://www.linkedin.com/in/philiprehberger)

## License

[MIT](LICENSE)
