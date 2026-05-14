<?php

use PHPUnit\Framework\TestCase;
use PHireScript\Runtime\Types\SuperTypes\Email;

require_once __DIR__ . '/Another.interface.php';
require_once __DIR__ . '/UserCredentials.php';
require_once __DIR__ . '/User.php';

class UserTest extends TestCase
{
    private string $ns = 'PHireScript\Sandbox\src\output';

    private function getReflection(): \ReflectionClass
    {
        $fqcn = $this->ns . '\User';

        if (!class_exists($fqcn)) {
            $this->fail("Class {$fqcn} does not exist");
        }

        return new \ReflectionClass($fqcn);
    }

    private function makeUser(): object
    {
        $fqcn = $this->ns . '\User';
        return new $fqcn(1, 'andre', 'andre@example.com', true);
    }

    public function testNamespaceIsCorrect(): void
    {
        $this->assertEquals($this->ns, $this->getReflection()->getNamespaceName());
    }

    public function testClassExistsAndIsNotInterface(): void
    {
        $reflection = $this->getReflection();
        $this->assertFalse($reflection->isInterface());
        $this->assertFalse($reflection->isTrait());
    }

    public function testCanInstantiate(): void
    {
        $user = $this->makeUser();
        $this->assertNotNull($user);
    }

    public function testIdIsSetCorrectly(): void
    {
        $user = $this->makeUser();
        $this->assertEquals(1, $user->id);
    }

    public function testUsernameIsSetCorrectly(): void
    {
        $user = $this->makeUser();
        $this->assertEquals('andre', $user->username);
    }

    public function testEmailIsStoredAsString(): void
    {
        $user = $this->makeUser();
        $this->assertIsString($user->email);
    }

    public function testEmailValueIsPreserved(): void
    {
        $user = $this->makeUser();
        $this->assertEquals('andre@example.com', $user->email);
    }

    public function testIsAdminIsSetCorrectly(): void
    {
        $user = $this->makeUser();
        $this->assertTrue($user->isAdmin);
    }

    public function testIsAdminDefaultIsTrue(): void
    {
        $reflection = $this->getReflection();
        $property = $reflection->getProperty('isAdmin');
        $this->assertTrue($property->getDefaultValue());
    }

    public function testIdPropertyIsPublicInt(): void
    {
        $reflection = $this->getReflection();
        $prop = $reflection->getProperty('id');
        $this->assertTrue($prop->isPublic());
        $this->assertEquals('int', $prop->getType()->getName());
    }

    public function testUsernamePropertyIsPublicString(): void
    {
        $reflection = $this->getReflection();
        $prop = $reflection->getProperty('username');
        $this->assertTrue($prop->isPublic());
        $this->assertEquals('string', $prop->getType()->getName());
    }

    public function testEmailPropertyIsPublicString(): void
    {
        $reflection = $this->getReflection();
        $prop = $reflection->getProperty('email');
        $this->assertTrue($prop->isPublic());
        $this->assertEquals('string', $prop->getType()->getName());
    }

    public function testIsAdminPropertyIsPublicBool(): void
    {
        $reflection = $this->getReflection();
        $prop = $reflection->getProperty('isAdmin');
        $this->assertTrue($prop->isPublic());
        $this->assertEquals('bool', $prop->getType()->getName());
    }

    public function testInvalidEmailThrowsException(): void
    {
        $this->expectException(\Throwable::class);
        $fqcn = $this->ns . '\User';
        new $fqcn(1, 'andre', 'not-a-valid-email', true);
    }
}
