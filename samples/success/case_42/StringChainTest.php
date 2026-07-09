<?php

use PHPUnit\Framework\TestCase;

class StringChainTest extends TestCase
{
    private array $vars = [];

    protected function setUp(): void
    {
        $this->vars = (function() {
            require __DIR__ . '/StringChain.php';
            return get_defined_vars();
        })();
    }

    public function testMystringValue(): void
    {
        $this->assertEquals('this is a string', $this->vars['mystring']);
    }

    public function testProcessedValue(): void
    {
        $this->assertEquals(30, $this->vars['processed']);
    }

    public function testUpperValue(): void
    {
        $this->assertEquals('THIS IS A STRING', $this->vars['upper']);
    }

    public function testChainThreeValue(): void
    {
        $this->assertEquals(18, $this->vars['chainThree']);
    }
}
