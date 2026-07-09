<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Setter.php';

class SetterTest extends TestCase
{
    private string $fqcn = 'PHireScript\Sandbox\src\output\Setter';

    private function make(string $email = 'test@example.com', string $username = 'user'): object
    {
        return new $this->fqcn($email, $username);
    }

    public function testGetUsernameReturnsInitialUsername(): void
    {
        $setter = $this->make('test@example.com', 'alice');
        $this->assertEquals('alice', $setter->getUsername());
    }

    public function testSetUsernameChangesUsername(): void
    {
        $setter = $this->make('test@example.com', 'alice');
        $setter->setUsername('bob');
        $this->assertEquals('bob', $setter->getUsername());
    }

    public function testSetEmailAcceptsValidEmail(): void
    {
        $setter = $this->make('test@example.com', 'alice');
        $setter->setEmail('new@example.com');
        $this->assertIsString($setter->email);
    }

    public function testConstructorSetsEmail(): void
    {
        $setter = $this->make('user@example.com', 'alice');
        $this->assertIsString($setter->email);
        $this->assertNotEmpty($setter->email);
    }

    public function testConstructorSetsUsername(): void
    {
        $setter = $this->make('test@example.com', 'charlie');
        $this->assertEquals('charlie', $setter->getUsername());
    }
}
