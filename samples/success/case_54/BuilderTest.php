<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Builder.php';

class BuilderTest extends TestCase
{
    private string $fqcn = 'PHireScript\Sandbox\src\output\Builder';

    private function make(string $name = 'initial', int $value = 0): object
    {
        return new $this->fqcn($name, $value);
    }

    public function testGetNameReturnsInitialName(): void
    {
        $builder = $this->make('initial', 0);
        $this->assertEquals('initial', $builder->getName());
    }

    public function testGetValueReturnsInitialValue(): void
    {
        $builder = $this->make('initial', 0);
        $this->assertEquals(0, $builder->getValue());
    }

    public function testWithNameSetsDefaultName(): void
    {
        $builder = $this->make('initial', 0);
        $builder->withName();
        $this->assertEquals('default', $builder->getName());
    }

    public function testWithValueSets42(): void
    {
        $builder = $this->make('initial', 0);
        $builder->withValue();
        $this->assertEquals(42, $builder->getValue());
    }

    public function testWithNameReturnsFluentInterface(): void
    {
        $builder = $this->make('initial', 0);
        $result = $builder->withName();
        $this->assertSame($builder, $result);
    }

    public function testWithValueReturnsFluentInterface(): void
    {
        $builder = $this->make('initial', 0);
        $result = $builder->withValue();
        $this->assertSame($builder, $result);
    }

    public function testFluentChain(): void
    {
        $builder = $this->make('initial', 0);
        $result = $builder->withName()->withValue();
        $this->assertSame($builder, $result);
        $this->assertEquals('default', $builder->getName());
        $this->assertEquals(42, $builder->getValue());
    }
}
