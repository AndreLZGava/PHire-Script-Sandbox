<?php

use PHPUnit\Framework\TestCase;

class UnaryNegationTest extends TestCase
{
    private object $obj;

    protected function setUp(): void
    {
        require_once __DIR__ . '/UnaryNegation.php';
        $this->obj = new \PHireScript\Sandbox\src\output\UnaryNegation(true, 5);
    }

    private function invoke(string $method): mixed
    {
        $r = new \ReflectionMethod($this->obj, $method);
        $r->setAccessible(true);
        return $r->invoke($this->obj);
    }

    public function testGetInverted(): void
    {
        $this->assertFalse($this->invoke('getInverted'));
    }

    public function testGetNegative(): void
    {
        $this->assertEquals(-5, $this->invoke('getNegative'));
    }
}
