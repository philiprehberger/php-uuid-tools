<?php

declare(strict_types=1);

namespace PhilipRehberger\UuidTools\Tests;

use PhilipRehberger\UuidTools\Ulid;
use PhilipRehberger\UuidTools\Uuid;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UlidTest extends TestCase
{
    #[Test]
    public function generate_returns_26_character_string(): void
    {
        $ulid = Ulid::generate();

        $this->assertSame(26, strlen($ulid));
    }

    #[Test]
    public function generate_returns_valid_crockford_base32(): void
    {
        $ulid = Ulid::generate();

        $this->assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{26}$/', $ulid);
    }

    #[Test]
    public function is_valid_accepts_valid_ulids(): void
    {
        $ulid = Ulid::generate();

        $this->assertTrue(Ulid::isValid($ulid));
        $this->assertTrue(Ulid::isValid('01ARZ3NDEKTSV4RRFFQ69G5FAV'));
    }

    #[Test]
    public function is_valid_rejects_invalid_strings(): void
    {
        $this->assertFalse(Ulid::isValid(''));
        $this->assertFalse(Ulid::isValid('too-short'));
        $this->assertFalse(Ulid::isValid('0000000000000000000000000!')); // invalid char
        $this->assertFalse(Ulid::isValid('00000000000000000000000000I')); // I not in Crockford
        $this->assertFalse(Ulid::isValid(str_repeat('0', 25))); // 25 chars
        $this->assertFalse(Ulid::isValid(str_repeat('0', 27))); // 27 chars
    }

    #[Test]
    public function to_uuid_and_from_uuid_round_trip(): void
    {
        // UUID → ULID → UUID is a perfect round-trip
        $uuid = Uuid::v4();
        $ulid = Ulid::fromUuid($uuid);
        $this->assertTrue(Ulid::isValid($ulid));

        $backToUuid = Ulid::toUuid($ulid);
        $this->assertSame($uuid, $backToUuid);
    }

    #[Test]
    public function timestamp_extracts_correct_millisecond_timestamp(): void
    {
        $beforeMs = (int) (microtime(true) * 1000);
        $ulid = Ulid::generate();
        $afterMs = (int) (microtime(true) * 1000);

        $timestamp = Ulid::timestamp($ulid);

        $this->assertGreaterThanOrEqual($beforeMs, $timestamp);
        $this->assertLessThanOrEqual($afterMs, $timestamp);
    }

    #[Test]
    public function two_sequential_ulids_are_lexicographically_ordered(): void
    {
        $first = Ulid::generate();
        usleep(2000); // 2ms to ensure different timestamp
        $second = Ulid::generate();

        $this->assertLessThan($second, $first);
    }

    #[Test]
    public function uuid_ulid_convenience_method_returns_valid_ulid(): void
    {
        $ulid = Uuid::ulid();

        $this->assertSame(26, strlen($ulid));
        $this->assertTrue(Uuid::isValidUlid($ulid));
    }
}
