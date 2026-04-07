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

    #[Test]
    public function equals_with_same_uuid(): void
    {
        $uuid = Uuid::v4();

        $this->assertTrue(Uuid::equals($uuid, $uuid));
    }

    #[Test]
    public function equals_with_different_case(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        $this->assertTrue(Uuid::equals($uuid, strtoupper($uuid)));
    }

    #[Test]
    public function equals_with_different_uuids(): void
    {
        $a = Uuid::v4();
        $b = Uuid::v4();

        $this->assertFalse(Uuid::equals($a, $b));
    }

    #[Test]
    public function compare_to_with_equal_uuids(): void
    {
        $uuid = Uuid::v4();

        $this->assertSame(0, Uuid::compareTo($uuid, $uuid));
    }

    #[Test]
    public function compare_to_with_v4_uuids(): void
    {
        $a = '00000000-0000-4000-8000-000000000000';
        $b = 'ffffffff-ffff-4fff-bfff-ffffffffffff';

        $this->assertSame(-1, Uuid::compareTo($a, $b));
        $this->assertSame(1, Uuid::compareTo($b, $a));
    }

    #[Test]
    public function compare_to_with_v7_uuids(): void
    {
        $first = Uuid::v7();
        usleep(1000);
        $second = Uuid::v7();

        $this->assertSame(-1, Uuid::compareTo($first, $second));
        $this->assertSame(1, Uuid::compareTo($second, $first));
    }

    #[Test]
    public function batch_generates_correct_count(): void
    {
        $uuids = Uuid::batch(10);

        $this->assertCount(10, $uuids);
    }

    #[Test]
    public function batch_generates_unique_uuids(): void
    {
        $uuids = Uuid::batch(50);

        $this->assertCount(50, array_unique($uuids));
    }

    #[Test]
    public function batch_generates_v4_by_default(): void
    {
        $uuids = Uuid::batch(5);

        foreach ($uuids as $uuid) {
            $this->assertTrue(Uuid::isValid($uuid));
            $this->assertSame(4, Uuid::version($uuid));
        }
    }

    #[Test]
    public function batch_generates_v7_when_specified(): void
    {
        $uuids = Uuid::batch(5, 7);

        foreach ($uuids as $uuid) {
            $this->assertTrue(Uuid::isValid($uuid));
            $this->assertSame(7, Uuid::version($uuid));
        }
    }

    #[Test]
    public function batch_throws_on_invalid_count(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Uuid::batch(0);
    }

    #[Test]
    public function batch_throws_on_unsupported_version(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Uuid::batch(5, 3);
    }

    #[Test]
    public function validate_batch_returns_indices_of_invalid_uuids(): void
    {
        $uuids = [
            '550e8400-e29b-41d4-a716-446655440000',
            'not-a-uuid',
            '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            '',
            'gggggggg-gggg-gggg-gggg-gggggggggggg',
        ];

        $this->assertSame([1, 3, 4], Uuid::validateBatch($uuids));
    }

    #[Test]
    public function validate_batch_returns_empty_when_all_valid(): void
    {
        $uuids = Uuid::batch(3);

        $this->assertSame([], Uuid::validateBatch($uuids));
    }
}
