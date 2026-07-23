<?php

use PHPUnit\Framework\TestCase;

class SafeNavigationTest extends TestCase
{
    private array $vars = [];

    protected function setUp(): void
    {
        $this->vars = (function() {
            require __DIR__ . '/SafeNavigation.php';
            return get_defined_vars();
        })();
    }

    public function testMystringValue(): void
    {
        $this->assertEquals('this is a test string', $this->vars['mystring']);
    }

    public function testResultIsNotNull(): void
    {
        $this->assertNotNull($this->vars['result']);
    }

    public function testResultValue(): void
    {
        $this->assertEquals(11, $this->vars['result']);
    }
}
