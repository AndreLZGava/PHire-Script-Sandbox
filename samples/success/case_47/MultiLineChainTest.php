<?php

use PHPUnit\Framework\TestCase;

class MultiLineChainTest extends TestCase
{
    private array $vars = [];

    protected function setUp(): void
    {
        $this->vars = (function() {
            require __DIR__ . '/MultiLineChain.php';
            return get_defined_vars();
        })();
    }

    public function testMystringValue(): void
    {
        $this->assertEquals('this is a string', $this->vars['mystring']);
    }

    public function testResultValue(): void
    {
        $this->assertEquals(3, $this->vars['result']);
    }
}
