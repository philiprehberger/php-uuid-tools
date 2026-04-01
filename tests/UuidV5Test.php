<?php

declare(strict_types=1);

namespace PhilipRehberger\UuidTools\Tests;

use InvalidArgumentException;
use PhilipRehberger\UuidTools\Uuid;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UuidV5Test extends TestCase
{
    #[Test]
    public function v5_with_dns_namespace_generates_deterministic_uuid(): void
    {
        $uuid = Uuid::v5(Uuid::NAMESPACE_DNS, 'example.com');

        $this->assertTrue(Uuid::isValid($uuid));
        // RFC 4122 test vector for v5 DNS + "example.com"
        $this->assertSame('cfbff0d1-9375-5685-968c-48ce8b15ae17', $uuid);
    }

    #[Test]
    public function v5_with_same_inputs_always_produces_same_output(): void
    {
        $uuid1 = Uuid::v5(Uuid::NAMESPACE_DNS, 'test.example.com');
        $uuid2 = Uuid::v5(Uuid::NAMESPACE_DNS, 'test.example.com');

        $this->assertSame($uuid1, $uuid2);
    }

    #[Test]
    public function v5_with_different_names_produces_different_uuids(): void
    {
        $uuid1 = Uuid::v5(Uuid::NAMESPACE_DNS, 'example.com');
        $uuid2 = Uuid::v5(Uuid::NAMESPACE_DNS, 'example.org');

        $this->assertNotSame($uuid1, $uuid2);
    }

    #[Test]
    public function v5_output_has_version_nibble_5(): void
    {
        $uuid = Uuid::v5(Uuid::NAMESPACE_DNS, 'example.com');

        $this->assertSame(5, Uuid::version($uuid));
    }

    #[Test]
    public function v5_with_url_namespace(): void
    {
        $uuid = Uuid::v5(Uuid::NAMESPACE_URL, 'https://example.com');

        $this->assertTrue(Uuid::isValid($uuid));
        $this->assertSame(5, Uuid::version($uuid));

        // Same input produces same output
        $uuid2 = Uuid::v5(Uuid::NAMESPACE_URL, 'https://example.com');
        $this->assertSame($uuid, $uuid2);
    }

    #[Test]
    public function v5_with_invalid_namespace_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Uuid::v5('not-a-valid-uuid', 'example.com');
    }
}
