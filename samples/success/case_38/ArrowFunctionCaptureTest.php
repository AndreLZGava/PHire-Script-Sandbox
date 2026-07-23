<?php

use PHPUnit\Framework\TestCase;

class ArrowFunctionCaptureTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $vars = [];

    protected function setUp(): void
    {
        $this->vars = (function () {
            require __DIR__ . '/ArrowFunctionCapture.php';
            return get_defined_vars();
        })();
    }

    public function testApplyDiscountIsCallable(): void
    {
        $this->assertArrayHasKey('applyDiscount', $this->vars);
        $this->assertIsCallable($this->vars['applyDiscount']);
    }

    public function testApplyDiscountCapturesDiscountVariable(): void
    {
        $applyDiscount = $this->vars['applyDiscount'];
        // The function returns $discount which was captured as 0.1
        $this->assertSame(0.1, $applyDiscount(100.0));
    }

    public function testBuildMessageIsCallable(): void
    {
        $this->assertArrayHasKey('buildMessage', $this->vars);
        $this->assertIsCallable($this->vars['buildMessage']);
    }

    public function testBuildMessageCapturesGreetingVariable(): void
    {
        $buildMessage = $this->vars['buildMessage'];
        // The function returns $greeting which was captured as "Hello"
        $this->assertSame('Hello', $buildMessage('World'));
    }

    public function testNoCaptureIsCallable(): void
    {
        $this->assertArrayHasKey('noCapture', $this->vars);
        $this->assertIsCallable($this->vars['noCapture']);
    }

    public function testNoCaptureReturnsPassedArgument(): void
    {
        $noCapture = $this->vars['noCapture'];
        $this->assertSame(42, $noCapture(42));
    }
}
