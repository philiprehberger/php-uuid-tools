<?php

declare(strict_types=1);

namespace PhilipRehberger\UuidTools;

use InvalidArgumentException;

/**
 * ULID (Universally Unique Lexicographically Sortable Identifier) utilities.
 */
final class Ulid
{
    private const CROCKFORD_BASE32 = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    private const VALID_PATTERN = '/^[0-9A-HJKMNP-TV-Z]{26}$/i';

    /**
     * Generate a new ULID.
     *
     * A ULID consists of a 48-bit timestamp (milliseconds since Unix epoch)
     * and 80 bits of randomness, encoded as 26 Crockford Base32 characters.
     */
    public static function generate(): string
    {
        $timestamp = (int) (microtime(true) * 1000);
        $randomBytes = random_bytes(10);

        // Encode timestamp (10 chars, 48 bits)
        $timestampChars = '';
        for ($i = 9; $i >= 0; $i--) {
            $timestampChars = self::CROCKFORD_BASE32[$timestamp & 0x1F].$timestampChars;
            $timestamp >>= 5;
        }

        // Encode randomness (16 chars, 80 bits)
        $randomChars = self::encodeBytesToBase32($randomBytes);

        return $timestampChars.$randomChars;
    }

    /**
     * Validate a ULID string.
     */
    public static function isValid(string $ulid): bool
    {
        if (strlen($ulid) !== 26) {
            return false;
        }

        return (bool) preg_match(self::VALID_PATTERN, $ulid);
    }

    /**
     * Convert a ULID to a UUID string.
     *
     * @throws InvalidArgumentException
     */
    public static function toUuid(string $ulid): string
    {
        if (! self::isValid($ulid)) {
            throw new InvalidArgumentException("Invalid ULID: '{$ulid}'.");
        }

        $bytes = self::decodeToBytes($ulid);
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

    /**
     * Convert a UUID string to a ULID.
     *
     * @throws InvalidArgumentException
     */
    public static function fromUuid(string $uuid): string
    {
        if (! Uuid::isValid($uuid)) {
            throw new InvalidArgumentException("Invalid UUID: '{$uuid}'.");
        }

        $hex = str_replace('-', '', $uuid);
        $bytes = hex2bin($hex);

        if ($bytes === false) {
            throw new InvalidArgumentException('Failed to convert UUID to bytes.');
        }

        return self::encodeBytesToUlid($bytes);
    }

    /**
     * Extract the Unix timestamp in milliseconds from a ULID.
     *
     * @throws InvalidArgumentException
     */
    public static function timestamp(string $ulid): int
    {
        if (! self::isValid($ulid)) {
            throw new InvalidArgumentException("Invalid ULID: '{$ulid}'.");
        }

        $timestampPart = strtoupper(substr($ulid, 0, 10));
        $timestamp = 0;

        for ($i = 0; $i < 10; $i++) {
            $value = strpos(self::CROCKFORD_BASE32, $timestampPart[$i]);
            $timestamp = ($timestamp << 5) | $value;
        }

        return $timestamp;
    }

    /**
     * Decode the timestamp portion of a ULID into a DateTimeImmutable.
     *
     * @throws InvalidArgumentException
     */
    public static function toDateTime(string $ulid, ?\DateTimeZone $tz = null): \DateTimeImmutable
    {
        $timestampMs = self::timestamp($ulid);
        $seconds = intdiv($timestampMs, 1000);
        $micros = ($timestampMs % 1000) * 1000;

        $dt = \DateTimeImmutable::createFromFormat(
            'U.u',
            sprintf('%d.%06d', $seconds, $micros),
            new \DateTimeZone('UTC'),
        );

        if ($dt === false) {
            throw new InvalidArgumentException("Failed to convert ULID timestamp: '{$ulid}'.");
        }

        return $dt->setTimezone($tz ?? new \DateTimeZone('UTC'));
    }

    /**
     * Encode 10 random bytes as 16 Crockford Base32 characters.
     */
    private static function encodeBytesToBase32(string $bytes): string
    {
        // Convert 10 bytes (80 bits) to 16 base32 chars (5 bits each)
        $result = '';
        $bitBuffer = 0;
        $bitsInBuffer = 0;

        for ($i = 0; $i < 10; $i++) {
            $bitBuffer = ($bitBuffer << 8) | ord($bytes[$i]);
            $bitsInBuffer += 8;

            while ($bitsInBuffer >= 5) {
                $bitsInBuffer -= 5;
                $result .= self::CROCKFORD_BASE32[($bitBuffer >> $bitsInBuffer) & 0x1F];
            }
        }

        return $result;
    }

    /**
     * Encode 16 bytes as a ULID string (26 Crockford Base32 characters).
     */
    private static function encodeBytesToUlid(string $bytes): string
    {
        // First 6 bytes = timestamp (10 chars)
        // Last 10 bytes = randomness (16 chars)
        $timestampBytes = substr($bytes, 0, 6);
        $randomBytes = substr($bytes, 6, 10);

        // Convert 6 timestamp bytes to integer then to 10 base32 chars
        $timestamp = 0;
        for ($i = 0; $i < 6; $i++) {
            $timestamp = ($timestamp << 8) | ord($timestampBytes[$i]);
        }

        $timestampChars = '';
        for ($i = 9; $i >= 0; $i--) {
            $timestampChars = self::CROCKFORD_BASE32[$timestamp & 0x1F].$timestampChars;
            $timestamp >>= 5;
        }

        return $timestampChars.self::encodeBytesToBase32($randomBytes);
    }

    /**
     * Decode a 26-character ULID to 16 bytes.
     *
     * Decodes timestamp (first 10 chars) and randomness (last 16 chars) separately,
     * matching the encoding approach in encodeBytesToUlid.
     */
    private static function decodeToBytes(string $ulid): string
    {
        $ulid = strtoupper($ulid);

        // Decode timestamp (first 10 chars → 48 bits → 6 bytes)
        $timestamp = 0;
        for ($i = 0; $i < 10; $i++) {
            $value = strpos(self::CROCKFORD_BASE32, $ulid[$i]);
            $timestamp = ($timestamp << 5) | $value;
        }

        $timestampBytes = '';
        for ($i = 5; $i >= 0; $i--) {
            $timestampBytes = chr($timestamp & 0xFF).$timestampBytes;
            $timestamp >>= 8;
        }

        // Decode randomness (last 16 chars → 80 bits → 10 bytes)
        $randomPart = substr($ulid, 10, 16);
        $bitBuffer = 0;
        $bitsInBuffer = 0;
        $randomBytes = '';

        for ($i = 0; $i < 16; $i++) {
            $value = strpos(self::CROCKFORD_BASE32, $randomPart[$i]);
            $bitBuffer = ($bitBuffer << 5) | $value;
            $bitsInBuffer += 5;

            while ($bitsInBuffer >= 8) {
                $bitsInBuffer -= 8;
                $randomBytes .= chr(($bitBuffer >> $bitsInBuffer) & 0xFF);
            }
        }

        return $timestampBytes.$randomBytes;
    }
}
