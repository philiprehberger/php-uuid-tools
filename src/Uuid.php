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
