<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Compiled/Classes/UserImmutable.php';

use PHireScript\Classes\UserImmutable;

class UserImmutableTest extends TestCase
{
    public function testCanInstantiateImmutableUser()
    {
        $user = new UserImmutable(
            1,
            'Andre',
            'example@exampple.com',
            true,
            null
        );

        $this->assertEquals(1, $user->id);
        $this->assertEquals('Andre', $user->username);
        $this->assertEquals('example@exampple.com', $user->email);
        $this->assertTrue($user->isAdmin);
        $this->assertNull($user->metadata);
    }

    public function testMetadataAcceptsArray()
    {
        $metadata = ['role' => 'admin'];

        $user = new UserImmutable(
            1,
            'Andre',
            'example@exampple.com',
            true,
            $metadata
        );

        $this->assertIsArray($user->metadata);
        $this->assertEquals($metadata, $user->metadata);
    }

    public function testReadonlyPropertiesCannotBeModified()
    {
        $user = new UserImmutable(
            1,
            'Andre',
            'example@exampple.com',
            true,
            null
        );

        $this->expectException(Error::class);

        $user->username = 'Hacked';
    }

    public function testConstructorTypeSafety()
    {
        $this->expectException(TypeError::class);

        new UserImmutable(
            'not-int',
            'Andre',
            'example@exampple.com',
            true,
            null
        );
    }
}
