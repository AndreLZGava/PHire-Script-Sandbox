<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/ExampleGetterSetterClass.php';

class ExampleGetterSetterClassTest extends TestCase
{
    private string $fqcn = 'PHireScript\Sandbox\src\output\ExampleGetterSetterClass';

    private function make(
        int $id = 1,
        string $email = 'test@example.com',
        string $username = 'user',
        bool $isAdmin = false,
        array $metadata = []
    ): object {
        return new $this->fqcn($id, $email, $username, $isAdmin, $metadata);
    }

    public function testGetIdReturnsCorrectValue(): void
    {
        $obj = $this->make(42);
        $this->assertEquals(42, $obj->getId());
    }

    public function testSetEmailAcceptsValidEmail(): void
    {
        $obj = $this->make(1, 'old@example.com', 'user');
        $obj->setEmail('new@example.com');
        $this->assertIsString($obj->email);
        $this->assertNotEmpty($obj->email);
    }

    public function testGetUsernameReturnsInitialValue(): void
    {
        $obj = $this->make(1, 'test@example.com', 'alice');
        $this->assertEquals('alice', $obj->getUsername());
    }

    public function testSetUsernameChangesValue(): void
    {
        $obj = $this->make(1, 'test@example.com', 'alice');
        $obj->setUsername('bob');
        $this->assertEquals('bob', $obj->getUsername());
    }

    public function testConstructorSetsAllPublicFields(): void
    {
        $obj = $this->make(7, 'user@example.com', 'carol', false, []);
        $this->assertEquals(7, $obj->getId());
        $this->assertEquals('carol', $obj->getUsername());
    }

    public function testGetIsAdminIsPrivate(): void
    {
        $reflection = new \ReflectionClass($this->fqcn);
        $method = $reflection->getMethod('getIsAdmin');
        $this->assertTrue($method->isPrivate());
    }

    public function testSetIsAdminIsProtected(): void
    {
        $reflection = new \ReflectionClass($this->fqcn);
        $method = $reflection->getMethod('setIsAdmin');
        $this->assertTrue($method->isProtected());
    }

    public function testGetMetadataIsProtected(): void
    {
        $reflection = new \ReflectionClass($this->fqcn);
        $method = $reflection->getMethod('getMetadata');
        $this->assertTrue($method->isProtected());
    }

    public function testSetMetadataIsPrivate(): void
    {
        $reflection = new \ReflectionClass($this->fqcn);
        $method = $reflection->getMethod('setMetadata');
        $this->assertTrue($method->isPrivate());
    }
}
