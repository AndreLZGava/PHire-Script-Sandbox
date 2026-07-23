<?php

use PHPUnit\Framework\TestCase;

class PersonTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/Person.php';
    }

    public function testConstructorAndGetters(): void
    {
        $person = new \PHireScript\Sandbox\src\output\Person('Alice', 30);
        $this->assertEquals('Alice', $person->getName());
        $this->assertEquals(30, $person->getAge());
    }

    public function testSetters(): void
    {
        $person = new \PHireScript\Sandbox\src\output\Person('Alice', 30);
        $person->setName('Bob');
        $person->setAge(25);
        $this->assertEquals('Bob', $person->getName());
        $this->assertEquals(25, $person->getAge());
    }

    public function testGreet(): void
    {
        $person = new \PHireScript\Sandbox\src\output\Person('Alice', 30);
        $this->assertEquals('Hello', $person->greet('Hello'));
    }
}
