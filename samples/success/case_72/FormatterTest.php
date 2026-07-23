<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Formatter.php';

class FormatterTest extends TestCase
{
    private string $fqcn = 'PHireScript\Sandbox\src\output\Formatter';

    public function testClassExists(): void
    {
        $this->assertTrue(class_exists($this->fqcn));
    }

    public function testPrefixPropertyIsPublic(): void
    {
        $r = new \ReflectionClass($this->fqcn);
        $this->assertTrue($r->hasProperty('prefix'));
        $this->assertTrue($r->getProperty('prefix')->isPublic());
    }

    public function testGetFormatterMethodExists(): void
    {
        $r = new \ReflectionClass($this->fqcn);
        $this->assertTrue($r->hasMethod('getFormatter'));
    }

    public function testConstructorSetsPrefix(): void
    {
        $obj = new $this->fqcn('test');
        $this->assertSame('test', $obj->prefix);
    }
}
