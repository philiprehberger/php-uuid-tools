<?php

declare(strict_types=1);

namespace PhilipRehberger\UuidTools\Tests;

use InvalidArgumentException;
use PhilipRehberger\UuidTools\ShortId;
use PhilipRehberger\UuidTools\Uuid;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ShortIdTest extends TestCase
{
    #[Test]
    public function encode_produces_short_string(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $shortId = ShortId::encode($uuid);

        $this->assertGreaterThanOrEqual(20, strlen($shortId));
        $this->assertLessThanOrEqual(22, strlen($shortId));
    }

    #[Test]
    public function decode_round_trips_back_to_original_uuid(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $shortId = ShortId::encode($uuid);
        $decoded = ShortId::decode($shortId);

        $this->assertSame($uuid, $decoded);
    }

    #[Test]
    public function encode_decode_with_v4_uuid(): void
    {
        $uuid = Uuid::v4();
        $shortId = ShortId::encode($uuid);
        $decoded = ShortId::decode($shortId);

        $this->assertSame($uuid, $decoded);
    }

    #[Test]
    public function encode_decode_with_v7_uuid(): void
    {
        $uuid = Uuid::v7();
        $shortId = ShortId::encode($uuid);
        $decoded = ShortId::decode($shortId);

        $this->assertSame($uuid, $decoded);
    }

    #[Test]
    public function decode_with_invalid_input_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ShortId::decode('!!!invalid!!!');
    }

    #[Test]
    public function decode_with_empty_string_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ShortId::decode('');
    }

    #[Test]
    public function uuid_convenience_methods_work(): void
    {
        $uuid = Uuid::v4();
        $shortId = Uuid::toShortId($uuid);
        $decoded = Uuid::fromShortId($shortId);

        $this->assertSame($uuid, $decoded);
    }
}
