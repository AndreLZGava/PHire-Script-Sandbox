<?php

use PHPUnit\Framework\TestCase;

class ExternalCallingStaticMethodsTest extends TestCase
{
    private string $source = '';

    protected function setUp(): void
    {
        $this->source = (string) file_get_contents(__DIR__ . '/ExternalCallingStaticMethods.php');
    }

    public function testFileExists(): void
    {
        $this->assertFileExists(__DIR__ . '/ExternalCallingStaticMethods.php');
    }

    public function testContainsStaticMethodCall(): void
    {
        $this->assertStringContainsString('PDO::getAvailableDrivers()', $this->source);
    }

    public function testContainsNewInstanceCall(): void
    {
        $this->assertStringContainsString('new PDO()', $this->source);
    }

    public function testContainsChainedMethodOnNewInstance(): void
    {
        $this->assertStringContainsString('->query(', $this->source);
    }

    public function testContainsFetchObject(): void
    {
        $this->assertStringContainsString('->fetchObject()', $this->source);
    }

    public function testContainsUsePDO(): void
    {
        $this->assertStringContainsString('use PDO', $this->source);
    }
}
