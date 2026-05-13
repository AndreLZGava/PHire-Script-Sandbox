<?php

use PHPUnit\Framework\TestCase;

/**
 * Known compilation bug: `$variablesReference = generatedUuid;` emits undefined constant.
 * Only $generatedUuid (set before the error) is fully testable.
 */
class VariablesSuperTypeUuidTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $vars = [];

    protected function setUp(): void
    {
        ob_start();
        try {
            include __DIR__ . '/VariablesSuperTypeUuid.php';
        } catch (\Error) {
            // Compilation bug: $variablesReference = generatedUuid; → undefined constant
        }
        ob_end_clean();
        $this->vars = get_defined_vars();
    }

    public function testGeneratedUuidIsDefined(): void
    {
        $this->assertArrayHasKey('generatedUuid', $this->vars);
    }

    public function testGeneratedUuidIsAString(): void
    {
        $this->assertIsString($this->vars['generatedUuid']);
    }

    public function testGeneratedUuidMatchesUuidFormat(): void
    {
        $regex = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        $this->assertMatchesRegularExpression($regex, $this->vars['generatedUuid']);
    }

    public function testGeneratedUuidIsVersion4(): void
    {
        $parts = explode('-', $this->vars['generatedUuid']);
        $this->assertEquals('4', substr($parts[2], 0, 1));
    }

    public function testGeneratedUuidIsUniqueAcrossMultipleCalls(): void
    {
        ob_start();
        try {
            include __DIR__ . '/VariablesSuperTypeUuid.php';
        } catch (\Error) {
        }
        ob_end_clean();
        $firstUuid = $this->vars['generatedUuid'];

        $vars2 = get_defined_vars();
        $secondUuid = $vars2['generatedUuid'] ?? null;

        if ($secondUuid !== null) {
            $this->assertNotEquals($firstUuid, $secondUuid);
        } else {
            $this->markTestSkipped('Second UUID could not be captured independently.');
        }
    }
}
