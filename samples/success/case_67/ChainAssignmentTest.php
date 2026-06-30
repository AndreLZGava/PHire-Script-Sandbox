<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/ChainAssignment.php';

class ChainAssignmentTest extends TestCase
{
    private function instance(): \PHireScript\Sandbox\src\output\ChainAssignment
    {
        return new \PHireScript\Sandbox\src\output\ChainAssignment('  hello world  ');
    }

    public function testProcessAssignmentReturnsUppercaseTrimmed(): void
    {
        $obj = $this->instance();
        $this->assertSame('HELLO WORLD', $obj->processAssignment());
    }

    public function testProcessReturnReturnsUppercaseTrimmed(): void
    {
        $obj = $this->instance();
        $this->assertSame('HELLO WORLD', $obj->processReturn());
    }

    public function testCompiledClassHasCorrectMethods(): void
    {
        $r = new \ReflectionClass(\PHireScript\Sandbox\src\output\ChainAssignment::class);
        $this->assertTrue($r->hasMethod('processAssignment'));
        $this->assertTrue($r->hasMethod('processReturn'));
    }
}
