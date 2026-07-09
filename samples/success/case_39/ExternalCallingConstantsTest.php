<?php

use PHPUnit\Framework\TestCase;

class ExternalCallingConstantsTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        $this->file = __DIR__ . '/ExternalCallingConstants.php';
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

    public function testStaticMethodCall(): void
    {
        $content = file_get_contents($this->file);
        $this->assertStringContainsString('DateTimePhp::createFromFormat', $content);
    }

    public function testConstantAccess(): void
    {
        $content = file_get_contents($this->file);
        $this->assertStringContainsString('DateTimePhp::ATOM', $content);
    }

    public function testInstanceMethodCall(): void
    {
        $content = file_get_contents($this->file);
        $this->assertStringContainsString('->format(', $content);
    }
}
