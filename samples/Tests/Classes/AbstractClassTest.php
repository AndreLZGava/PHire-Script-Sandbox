<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Compiled/Classes/AbstractClass.php';

class AbstractClassTest extends TestCase
{
    public function testThrowsExceptionWhenTableNameNotInitialized()
    {
        $this->expectException(LogicException::class);

        new class extends \PHireScript\Classes\AbstractClass {
        };
    }

    public function testDoesNotThrowWhenTableNameInitialized()
    {
        $instance = new class extends \PHireScript\Classes\AbstractClass {
            public string $tableName = 'users';
        };

        $this->assertEquals('users', $instance->tableName);
    }

    public function testMethodExampleReturnsNull()
    {
        $instance = new class extends \PHireScript\Classes\AbstractClass {
            public string $tableName = 'users';
        };

        $this->assertNull($instance->methodExample());
    }
}
