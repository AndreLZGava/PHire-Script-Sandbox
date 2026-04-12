<?php

use PHireScript\Sandbox\src\output\UserImmutable;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/UserImmutable.php';

class UserImmutableTest extends TestCase
{
    public function testCanInstantiateUser()
    {
        $user = new UserImmutable(
            1,
            'Andre',
            'andrelzgava@gmail.com',
            true,
            ['role' => 'admin']
        );

        $this->assertEquals(1, $user->id);
        $this->assertEquals('Andre', $user->username);
        $this->assertEquals('andrelzgava@gmail.com', $user->email);
        $this->assertTrue($user->isAdmin);
        $this->assertIsArray($user->metadata);
    }

    public function testMetadataCanBeNull()
    {
        $user = new UserImmutable(
            1,
            'Andre',
            'andrelzgava@gmail.com',
            false,
            null
        );

        $this->assertNull($user->metadata);
    }

    public function testInvalidEmailThrowsException()
    {
        $this->expectException(\Throwable::class);

        new UserImmutable(
            1,
            'Andre',
            'invalid-email',
            true,
            []
        );
    }

    public function testInvalidMetadataTypeThrowsTypeError()
    {
        $this->expectException(\TypeError::class);

        new UserImmutable(
            1,
            'Andre',
            'andrelzgava@gmail.com',
            true,
            'not-an-array'
        );
    }

    public function testPropertyTypes()
    {
        $user = new UserImmutable(
            1,
            'Andre',
            'andrelzgava@gmail.com',
            true,
            []
        );

        $this->assertIsInt($user->id);
        $this->assertIsString($user->username);
        $this->assertIsString($user->email);
        $this->assertIsBool($user->isAdmin);
        $this->assertIsArray($user->metadata);
    }
}
