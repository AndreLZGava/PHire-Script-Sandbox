<?php

namespace PHireScript\Tests\Runtime\Types\SuperTypes;

use PHPUnit\Framework\TestCase;
use PHireScript\Runtime\Types\SuperTypes\Uuid;
use TypeError;

class UuidTest extends TestCase
{
    public function testCastValidUuid(): void
    {
        $uuid = '550E8400-E29B-41D4-A716-446655440000';

        $result = Uuid::cast($uuid);

        $this->assertIsString($result);
        $this->assertSame(strtolower($uuid), $result);
    }

    public function testCastValidLowercaseUuid(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        $result = Uuid::cast($uuid);

        $this->assertSame($uuid, $result);
    }


    public function testCastInvalidUuid(): void
    {
        $this->expectException(TypeError::class);

        Uuid::cast('not-a-uuid');
    }

    public function testCastMalformedUuid(): void
    {
        $this->expectException(TypeError::class);

        Uuid::cast('550e8400-e29b-zzzz-a716-446655440000');
    }

    public function testCastNonString(): void
    {
        $this->expectException(TypeError::class);

        Uuid::cast(123);
    }

    public function testCastBoolean(): void
    {
        $this->expectException(TypeError::class);

        Uuid::cast(true);
    }

    public function testCastWithoutValueGeneratesUuid(): void
    {
        $uuid = Uuid::cast();

        $this->assertIsString($uuid);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid
        );
    }

    public function testCastNullGeneratesUuid(): void
    {
        $uuid = Uuid::cast(null);

        $this->assertIsString($uuid);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid
        );
    }


    public function testCastEmptyStringGeneratesUuid(): void
    {
        $uuid = Uuid::cast('');

        $this->assertIsString($uuid);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid
        );
    }
}
