<?php

use PHPUnit\Framework\TestCase;

class ChainInExpressionTest extends TestCase
{
    private array $vars = [];

    protected function setUp(): void
    {
        $this->vars = (function() {
            require __DIR__ . '/ChainInExpression.php';
            return get_defined_vars();
        })();
    }

    public function testMystringValue(): void
    {
        $this->assertEquals('this is a string', $this->vars['mystring']);
    }

    public function testLenValue(): void
    {
        $this->assertEquals(16, $this->vars['len']);
    }

    public function testUpperValue(): void
    {
        $this->assertEquals('THIS IS A STRING', $this->vars['upper']);
    }
}
