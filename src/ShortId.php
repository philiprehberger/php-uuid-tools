<?php

declare(strict_types=1);

namespace PhilipRehberger\UuidTools;

use InvalidArgumentException;

/**
 * Base62 short ID encoding and decoding for UUIDs.
 */
final class ShortId
{
    private const ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /**
     * Encode a UUID string to a Base62 short ID (~22 characters).
     *
     * @throws InvalidArgumentException
     */
    public static function encode(string $uuid): string
    {
        if (! Uuid::isValid($uuid)) {
            throw new InvalidArgumentException("Invalid UUID: '{$uuid}'.");
        }

        $hex = str_replace('-', '', $uuid);

        return self::hexToBase62($hex);
    }

    /**
     * Decode a Base62 short ID back to a UUID string.
     *
     * @throws InvalidArgumentException
     */
    public static function decode(string $shortId): string
    {
        if ($shortId === '' || ! preg_match('/^[0-9A-Za-z]+$/', $shortId)) {
            throw new InvalidArgumentException("Invalid short ID: '{$shortId}'.");
        }

        $hex = self::base62ToHex($shortId);

        // Pad to 32 hex chars
        $hex = str_pad($hex, 32, '0', STR_PAD_LEFT);

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
     * Convert a hex string to Base62 using bcmath.
     */
    private static function hexToBase62(string $hex): string
    {
        $number = self::hexToBc($hex);

        if ($number === '0') {
            return '0';
        }

        $result = '';

        while (bccomp($number, '0') > 0) {
            $remainder = bcmod($number, '62');
            $result = self::ALPHABET[(int) $remainder].$result;
            $number = bcdiv($number, '62', 0);
        }

        return $result;
    }

    /**
     * Convert a Base62 string to hex using bcmath.
     */
    private static function base62ToHex(string $base62): string
    {
        $number = '0';

        for ($i = 0; $i < strlen($base62); $i++) {
            $char = $base62[$i];
            $value = strpos(self::ALPHABET, $char);

            if ($value === false) {
                throw new InvalidArgumentException("Invalid character '{$char}' in short ID.");
            }

            $number = bcadd(bcmul($number, '62'), (string) $value);
        }

        return self::bcToHex($number);
    }

    /**
     * Convert a hexadecimal string to a bcmath decimal string.
     */
    private static function hexToBc(string $hex): string
    {
        $result = '0';

        for ($i = 0; $i < strlen($hex); $i++) {
            $digit = hexdec($hex[$i]);
            $result = bcadd(bcmul($result, '16'), (string) $digit);
        }

        return $result;
    }

    /**
     * Convert a bcmath decimal string to a hexadecimal string.
     */
    private static function bcToHex(string $number): string
    {
        if ($number === '0') {
            return '0';
        }

        $hex = '';

        while (bccomp($number, '0') > 0) {
            $remainder = (int) bcmod($number, '16');
            $hex = dechex($remainder).$hex;
            $number = bcdiv($number, '16', 0);
        }

        return $hex;
    }
}
