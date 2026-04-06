<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Compiled/Classes/User.php';

use PHireScript\Classes\User;

class UserTest extends TestCase
{
    public function testCanInstantiateUser()
    {
        $user = new User(
            1,
            'Andre',
            'andrelzgava@gmail.com',
            true,
            null
        );

        $this->assertEquals(1, $user->id);
        $this->assertEquals('Andre', $user->username);
        $this->assertEquals('andrelzgava@gmail.com', $user->email);
        $this->assertTrue($user->isAdmin);
        $this->assertNull($user->metadata);
    }

    public function testMetadataAcceptsArray()
    {
        $metadata = ['role' => 'admin'];

        $user = new User(
            1,
            'Andre',
            'andrelzgava@gmail.com',
            true,
            $metadata
        );

        $this->assertIsArray($user->metadata);
        $this->assertEquals($metadata, $user->metadata);
    }

    public function testConstructorTypeSafety()
    {
        $this->expectException(TypeError::class);

        new User(
            'not-int 😂',
            'Andre',
            'andrelzgava@gmail.com',
            true,
            null
        );
    }

    public function testEmailIsAlwaysString()
    {
        $user = new User(
            1,
            'Andre',
            'andrelzgava@gmail.com',
            true,
            null
        );

        $this->assertIsString($user->email);
    }
}
