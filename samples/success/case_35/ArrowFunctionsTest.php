<?php

use PHPUnit\Framework\TestCase;

class ArrowFunctionsTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $vars = [];

    protected function setUp(): void
    {
        $this->vars = (function () {
            require __DIR__ . '/ArrowFunctions.php';
            return get_defined_vars();
        })();
    }

    public function testGreetIsCallable(): void
    {
        $this->assertArrayHasKey('greet', $this->vars);
        $this->assertIsCallable($this->vars['greet']);
    }

    public function testIdentityIsCallable(): void
    {
        $this->assertArrayHasKey('identity', $this->vars);
        $this->assertIsCallable($this->vars['identity']);
    }

    public function testIdentityReturnsPassedValue(): void
    {
        $identity = $this->vars['identity'];
        $this->assertSame(5, $identity(5));
    }
}
