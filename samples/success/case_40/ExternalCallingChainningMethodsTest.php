<?php

use PHPUnit\Framework\TestCase;

class ExternalCallingChainningMethodsTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        $this->file = __DIR__ . '/ExternalCallingChainningMethods.php';
    }

    public function testFileExists(): void
    {
        $this->assertFileExists($this->file);
    }

    public function testUsesDateTimeAlias(): void
    {
        $content = file_get_contents($this->file);
        $this->assertStringContainsString('use DateTime as DateTimePhp', $content);
    }

    public function testInstantiatesWithNew(): void
    {
        $content = file_get_contents($this->file);
        $this->assertStringContainsString('new DateTimePhp()', $content);
    }

    public function testCallsModify(): void
    {
        $content = file_get_contents($this->file);
        $this->assertStringContainsString("->modify('+3 days')", $content);
    }

    public function testCallsFormat(): void
    {
        $content = file_get_contents($this->file);
        $this->assertStringContainsString("->format(", $content);
    }

    public function testResultAssigned(): void
    {
        $content = file_get_contents($this->file);
        $this->assertStringContainsString('$result', $content);
    }
}
