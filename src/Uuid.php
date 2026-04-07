<?php

declare(strict_types=1);

namespace PhilipRehberger\UuidTools;

use InvalidArgumentException;

/**
 * UUID generation, validation, and conversion utilities.
 */
final class Uuid
{
    private const VALID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    /** DNS namespace UUID (RFC 4122). */
    public const NAMESPACE_DNS = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

    /** URL namespace UUID (RFC 4122). */
    public const NAMESPACE_URL = '6ba7b811-9dad-11d1-80b4-00c04fd430c8';

    /** OID namespace UUID (RFC 4122). */
    public const NAMESPACE_OID = '6ba7b812-9dad-11d1-80b4-00c04fd430c8';

    /** X.500 DN namespace UUID (RFC 4122). */
    public const NAMESPACE_X500 = '6ba7b814-9dad-11d1-80b4-00c04fd430c8';

    /**
     * Generate a random UUID v4.
     */
    public static function v4(): string
    {
        $bytes = random_bytes(16);
        // Set version 4 (0100)
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
        // Set variant 10xx
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        return self::formatBytes($bytes);
    }

    /**
     * Generate a time-ordered UUID v7.
     */
    public static function v7(): string
    {
        // Get millisecond timestamp
        $timestamp = (int) (microtime(true) * 1000);
        $tsBytes = pack('J', $timestamp); // 8 bytes big-endian
        // Use last 6 bytes (48 bits of millisecond timestamp)
        $tsBytes = substr($tsBytes, 2, 6);

        $randBytes = random_bytes(10);

        $bytes = $tsBytes.$randBytes;

        // Set version 7 (0111)
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x70);
        // Set variant 10xx
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        return self::formatBytes($bytes);
    }

    /**
     * Validate a UUID string (any version).
     */
    public static function isValid(string $uuid): bool
    {
        return (bool) preg_match(self::VALID_PATTERN, $uuid);
    }

    /**
     * Extract the version number from a UUID string.
     */
    public static function version(string $uuid): ?int
    {
        if (! self::isValid($uuid)) {
            return null;
        }

        // Version is the first nibble of the 7th byte (position 12 in hex without dashes)
        $clean = str_replace('-', '', $uuid);

        return (int) $clean[12];
    }

    /**
     * Convert a UUID string to 16-byte binary.
     *
     * @throws InvalidArgumentException
     */
    public static function toBytes(string $uuid): string
    {
        if (! self::isValid($uuid)) {
            throw new InvalidArgumentException("Invalid UUID: '{$uuid}'.");
        }

        $hex = str_replace('-', '', $uuid);

        return hex2bin($hex) ?: throw new InvalidArgumentException('Failed to convert UUID to bytes.');
    }

    /**
     * Convert 16-byte binary to a UUID string.
     *
     * @throws InvalidArgumentException
     */
    public static function fromBytes(string $bytes): string
    {
        if (strlen($bytes) !== 16) {
            throw new InvalidArgumentException('Bytes must be exactly 16 bytes long.');
        }

        return self::formatBytes($bytes);
    }

    /**
     * Reorder a UUID for optimal database index performance.
     *
     * Swaps time-high, time-mid, and time-low fields so that time-ordered
     * UUIDs sort lexicographically in database indexes.
     *
     * Standard: AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE
     * Ordered:  CCCCBBBB-AAAA-AAAA-DDDD-EEEEEEEEEEEE
     *
     * @throws InvalidArgumentException
     */
    public static function toOrdered(string $uuid): string
    {
        if (! self::isValid($uuid)) {
            throw new InvalidArgumentException("Invalid UUID: '{$uuid}'.");
        }

        $hex = str_replace('-', '', $uuid);
        $timeLow = substr($hex, 0, 8);
        $timeMid = substr($hex, 8, 4);
        $timeHi = substr($hex, 12, 4);
        $clock = substr($hex, 16, 4);
        $node = substr($hex, 20, 12);

        $ordered = $timeHi.$timeMid.$timeLow.$clock.$node;

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($ordered, 0, 8),
            substr($ordered, 8, 4),
            substr($ordered, 12, 4),
            substr($ordered, 16, 4),
            substr($ordered, 20, 12),
        );
    }

    /**
     * Reverse an ordered UUID back to standard format.
     *
     * @throws InvalidArgumentException
     */
    public static function fromOrdered(string $ordered): string
    {
        if (! self::isValid($ordered)) {
            throw new InvalidArgumentException("Invalid ordered UUID: '{$ordered}'.");
        }

        $hex = str_replace('-', '', $ordered);
        // Reverse: timeHi(4) timeMid(4) timeLow(8) clock(4) node(12)
        $timeHi = substr($hex, 0, 4);
        $timeMid = substr($hex, 4, 4);
        $timeLow = substr($hex, 8, 8);
        $clock = substr($hex, 16, 4);
        $node = substr($hex, 20, 12);

        return sprintf(
            '%s-%s-%s-%s-%s',
            $timeLow,
            $timeMid,
            $timeHi,
            $clock,
            $node,
        );
    }

    /**
     * Check if two UUID strings are equal (case-insensitive).
     */
    public static function equals(string $a, string $b): bool
    {
        return strtolower($a) === strtolower($b);
    }

    /**
     * Compare two UUID strings lexicographically.
     *
     * Returns -1, 0, or 1 for use in sorting.
     */
    public static function compareTo(string $a, string $b): int
    {
        return strtolower($a) <=> strtolower($b);
    }

    /**
     * Generate a deterministic UUID v5 using SHA-1 hashing.
     *
     * @param  string  $namespace  A valid UUID to use as namespace
     * @param  string  $name  The name to hash within the namespace
     *
     * @throws InvalidArgumentException
     */
    public static function v5(string $namespace, string $name): string
    {
        if (! self::isValid($namespace)) {
            throw new InvalidArgumentException("Invalid namespace UUID: '{$namespace}'.");
        }

        $namespaceBytes = self::toBytes($namespace);
        $hash = sha1($namespaceBytes.$name);

        // Take first 16 bytes (32 hex chars) of SHA-1 hash
        $hex = substr($hash, 0, 32);
        $bytes = hex2bin($hex);

        // Set version 5 (0101)
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x50);
        // Set variant 10xx
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        return self::formatBytes($bytes);
    }

    /**
     * Generate a ULID (Universally Unique Lexicographically Sortable Identifier).
     */
    public static function ulid(): string
    {
        return Ulid::generate();
    }

    /**
     * Validate a ULID string.
     */
    public static function isValidUlid(string $ulid): bool
    {
        return Ulid::isValid($ulid);
    }

    /**
     * Encode a UUID as a Base62 short ID (~22 characters).
     *
     * @throws InvalidArgumentException
     */
    public static function toShortId(string $uuid): string
    {
        return ShortId::encode($uuid);
    }

    /**
     * Decode a Base62 short ID back to a UUID string.
     *
     * @throws InvalidArgumentException
     */
    public static function fromShortId(string $shortId): string
    {
        return ShortId::decode($shortId);
    }

    /**
     * Generate multiple UUIDs at once.
     *
     * @param  int  $count  Number of UUIDs to generate
     * @param  int  $version  UUID version (4 or 7)
     * @return string[]
     *
     * @throws InvalidArgumentException
     */
    public static function batch(int $count, int $version = 4): array
    {
        if ($count < 1) {
            throw new InvalidArgumentException('Count must be at least 1.');
        }

        if ($version !== 4 && $version !== 7) {
            throw new InvalidArgumentException("Unsupported UUID version: {$version}. Use 4 or 7.");
        }

        $uuids = [];
        for ($i = 0; $i < $count; $i++) {
            $uuids[] = $version === 4 ? self::v4() : self::v7();
        }

        return $uuids;
    }

    /**
     * Validate a list of UUIDs and return the indices of invalid entries.
     *
     * @param  array<int, string>  $uuids
     * @return list<int>
     */
    public static function validateBatch(array $uuids): array
    {
        $invalid = [];
        foreach (array_values($uuids) as $index => $uuid) {
            if (! self::isValid($uuid)) {
                $invalid[] = $index;
            }
        }

        return $invalid;
    }

    /**
     * Return the nil UUID (all zeros).
     */
    public static function nil(): string
    {
        return '00000000-0000-0000-0000-000000000000';
    }

    /**
     * Format 16 bytes as a UUID string.
     */
    private static function formatBytes(string $bytes): string
    {
        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }
}
