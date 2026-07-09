<?php

use PHPUnit\Framework\TestCase;

class CrossTypeChainTest extends TestCase
{
    private array $vars = [];

    protected function setUp(): void
    {
        $this->vars = (function() {
            require __DIR__ . '/CrossTypeChain.php';
            return get_defined_vars();
        })();
    }

    public function testMystringValue(): void
    {
        $this->assertEquals('this is a test string', $this->vars['mystring']);
    }

    public function testPartsIsArray(): void
    {
        $this->assertIsArray($this->vars['parts']);
    }

    public function testPartsValue(): void
    {
        $this->assertEquals(['this', 'is', 'a', 'test', 'string'], $this->vars['parts']);
    }

    public function testCountValue(): void
    {
        $this->assertEquals(5, $this->vars['count']);
    }

    public function testChainedCountValue(): void
    {
        $this->assertEquals(5, $this->vars['chainedCount']);
    }
}
