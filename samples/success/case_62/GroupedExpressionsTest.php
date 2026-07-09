<?php

use PHPUnit\Framework\TestCase;

class GroupedExpressionsTest extends TestCase
{
    public function testGetGrouped(): void
    {
        require_once __DIR__ . '/GroupedExpressions.php';
        $obj = new \PHireScript\Sandbox\src\output\GroupedExpressions(2.0, 3.0, 4.0, 0.0);
        $r = new \ReflectionMethod($obj, 'getGrouped');
        $r->setAccessible(true);
        $this->assertEqualsWithDelta(20.0, $r->invoke($obj), 0.001);
    }
}
