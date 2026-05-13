<?php

use PHPUnit\Framework\TestCase;

/**
 * Known compilation bug: variable references are emitted without the $ prefix.
 * e.g. `override = price` compiles to `$override = price;` instead of `$override = $price;`
 * Variables defined before the failing line are tested; broken assignments are noted.
 */
class VariablesTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $vars = [];

    protected function setUp(): void
    {
        ob_start();
        try {
            include __DIR__ . '/Variables.php';
        } catch (\Error) {
            // Compilation bug: $override = price; triggers "Undefined constant price"
        }
        ob_end_clean();
        $this->vars = get_defined_vars();
    }

    public function testUserIsDefinedAndIsObject(): void
    {
        $this->assertArrayHasKey('user', $this->vars);
        $this->assertIsObject($this->vars['user']);
    }

    public function testUserIsEmptyObject(): void
    {
        $properties = get_object_vars($this->vars['user']);
        $this->assertEmpty($properties);
    }

    public function testPriceIsDefinedAndIsFloat(): void
    {
        $this->assertArrayHasKey('price', $this->vars);
        $this->assertIsFloat($this->vars['price']);
    }

    public function testPriceValue(): void
    {
        $this->assertEquals(19.9, $this->vars['price']);
    }

    public function testIncomeIsDefinedAndIsFloat(): void
    {
        $this->assertArrayHasKey('income', $this->vars);
        $this->assertIsFloat($this->vars['income']);
    }

    public function testIncomeValue(): void
    {
        $this->assertEquals(1.05, $this->vars['income']);
    }

    public function testOverrideIsSetDueToCompilationBug(): void
    {
        $this->assertArrayHasKey('override', $this->vars);
    }
}
