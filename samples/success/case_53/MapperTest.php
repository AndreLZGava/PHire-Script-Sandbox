<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Mapper.php';

class MapperTest extends TestCase
{
    private string $fqcn = 'PHireScript\Sandbox\src\output\Mapper';

    private function make(string $prefix): object
    {
        return new $this->fqcn($prefix);
    }

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

    public function testGetTransformerMethodExists(): void
    {
        $r = new \ReflectionClass($this->fqcn);
        $this->assertTrue($r->hasMethod('getTransformer'));
    }

    public function testConstructorSetsPrefix(): void
    {
        $mapper = $this->make('hello');
        $this->assertEquals('hello', $mapper->prefix);
    }
}
