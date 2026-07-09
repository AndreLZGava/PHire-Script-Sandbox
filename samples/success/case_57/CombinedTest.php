<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Combined.php';

class CombinedTest extends TestCase
{
    private string $fqcn = 'PHireScript\Sandbox\src\output\Combined';

    private function make(string $username = 'alice', int $count = 0, bool $active = true): object
    {
        return new $this->fqcn($username, $count, $active);
    }

    public function testGetUsernameReturnsInitialValue(): void
    {
        $combined = $this->make('alice', 0, true);
        $this->assertEquals('alice', $combined->getUsername());
    }

    public function testSetUsernameChangesValue(): void
    {
        $combined = $this->make('alice', 0, true);
        $combined->setUsername('bob');
        $this->assertEquals('bob', $combined->getUsername());
    }

    public function testGetCountReturnsInitialValue(): void
    {
        $combined = $this->make('alice', 10, true);
        $this->assertEquals(10, $combined->getCount());
    }

    public function testSetCountChangesValue(): void
    {
        $combined = $this->make('alice', 0, true);
        $combined->setCount(99);
        $this->assertEquals(99, $combined->getCount());
    }

    public function testGetActiveReturnsInitialValue(): void
    {
        $combined = $this->make('alice', 0, true);
        $this->assertTrue($combined->getActive());
    }

    public function testSetActiveChangesValue(): void
    {
        $combined = $this->make('alice', 0, true);
        $combined->setActive(false);
        $this->assertFalse($combined->getActive());
    }

    public function testConstructorSetsAllFields(): void
    {
        $combined = $this->make('carol', 5, false);
        $this->assertEquals('carol', $combined->getUsername());
        $this->assertEquals(5, $combined->getCount());
        $this->assertFalse($combined->getActive());
    }
}
