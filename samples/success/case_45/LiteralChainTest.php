<?php

use PHPUnit\Framework\TestCase;

class LiteralChainTest extends TestCase
{
    private array $vars = [];

    protected function setUp(): void
    {
        $this->vars = (function() {
            require __DIR__ . '/LiteralChain.php';
            return get_defined_vars();
        })();
    }

    public function testResultValue(): void
    {
        $this->assertEquals(9, $this->vars['result']);
    }

    public function testUpperValue(): void
    {
        $this->assertEquals('MY STRING', $this->vars['upper']);
    }

    public function testReplacedValue(): void
    {
        $this->assertEquals('our string', $this->vars['replaced']);
    }
}
