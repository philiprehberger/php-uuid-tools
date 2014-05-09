<?php

declare(strict_types=1);

namespace PhilipRehberger\UuidTools\Tests;

use InvalidArgumentException;
use PhilipRehberger\UuidTools\Uuid;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UuidTest extends TestCase
{
    #[Test]
    public function v4_produces_valid_uuid_format(): void
    {
        $uuid = Uuid::v4();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid,
        );
    }

    #[Test]
    public function v4_produces_unique_values(): void
    {
        $uuids = [];
        for ($i = 0; $i < 100; $i++) {
            $uuids[] = Uuid::v4();
        }

        $this->assertCount(100, array_unique($uuids));
    }

    #[Test]
    public function v4_has_version_4(): void
    {
        $uuid = Uuid::v4();

        $this->assertSame(4, Uuid::version($uuid));
    }

    #[Test]
    public function v7_produces_valid_uuid_format(): void
    {
        $uuid = Uuid::v7();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid,
        );
    }

    #[Test]
    public function v7_has_version_7(): void
    {
        $uuid = Uuid::v7();

        $this->assertSame(7, Uuid::version($uuid));
    }

    #[Test]
    public function v7_is_monotonically_ordered(): void
    {
        $first = Uuid::v7();
        usleep(1000); // 1ms to ensure different timestamp
        $second = Uuid::v7();

        $this->assertGreaterThan($first, $second);
    }

    #[Test]
    public function is_valid_with_valid_lowercase_uuid(): void
    {
        $this->assertTrue(Uuid::isValid('550e8400-e29b-41d4-a716-446655440000'));
    }

    #[Test]
    public function is_valid_with_valid_uppercase_uuid(): void
    {
        $this->assertTrue(Uuid::isValid('550E8400-E29B-41D4-A716-446655440000'));
    }

    #[Test]
    public function is_valid_with_invalid_strings(): void
    {
        $this->assertFalse(Uuid::isValid(''));
        $this->assertFalse(Uuid::isValid('not-a-uuid'));
        $this->assertFalse(Uuid::isValid('550e8400-e29b-41d4-a716'));
        $this->assertFalse(Uuid::isValid('550e8400e29b41d4a716446655440000')); // no dashes
        $this->assertFalse(Uuid::isValid('gggggggg-gggg-gggg-gggg-gggggggggggg')); // invalid hex
    }

    #[Test]
    public function version_extraction_returns_correct_version(): void
    {
        $this->assertSame(4, Uuid::version('550e8400-e29b-41d4-a716-446655440000'));
        $this->assertSame(1, Uuid::version('6ba7b810-9dad-11d1-80b4-00c04fd430c8'));
        $this->assertNull(Uuid::version('invalid'));
    }

    #[Test]
    public function to_bytes_and_from_bytes_round_trip(): void
    {
        $uuid = Uuid::v4();
        $bytes = Uuid::toBytes($uuid);

        $this->assertSame(16, strlen($bytes));
        $this->assertSame($uuid, Uuid::fromBytes($bytes));
    }

    #[Test]
    public function to_bytes_throws_on_invalid_uuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Uuid::toBytes('invalid');
    }

    #[Test]
    public function to_ordered_and_from_ordered_round_trip(): void
    {
        $uuid = Uuid::v4();
        $ordered = Uuid::toOrdered($uuid);

        $this->assertTrue(Uuid::isValid($ordered));
        $this->assertNotSame($uuid, $ordered);
        $this->assertSame($uuid, Uuid::fromOrdered($ordered));
    }

    #[Test]
    public function nil_returns_all_zeros_uuid(): void
    {
        $nil = Uuid::nil();

        $this->assertSame('00000000-0000-0000-0000-000000000000', $nil);
        $this->assertTrue(Uuid::isValid($nil));
    }
}
