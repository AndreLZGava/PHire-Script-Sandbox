<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/SafeLogger.php';

class SafeLoggerTest extends TestCase
{
    private string $fqcn = 'PHireScript\Sandbox\src\output\SafeLogger';

    private function make(string $log = 'init', bool $hasError = false): object
    {
        return new $this->fqcn($log, $hasError);
    }

    public function testGetLogReturnsInitialLog(): void
    {
        $logger = $this->make('init', false);
        $this->assertEquals('init', $logger->getLog());
    }

    public function testHasErrorsReturnsFalseInitially(): void
    {
        $logger = $this->make('init', false);
        $this->assertFalse($logger->hasErrors());
    }

    public function testMarkErrorSetsLogToError(): void
    {
        $logger = $this->make('init', false);
        $logger->markError();
        $this->assertEquals('error', $logger->getLog());
    }

    public function testMarkErrorSetsHasErrorToTrue(): void
    {
        $logger = $this->make('init', false);
        $logger->markError();
        $this->assertTrue($logger->hasErrors());
    }

    public function testClearResetsLog(): void
    {
        $logger = $this->make('error', true);
        $logger->clear();
        $this->assertEquals('', $logger->getLog());
    }

    public function testClearResetsHasError(): void
    {
        $logger = $this->make('error', true);
        $logger->clear();
        $this->assertFalse($logger->hasErrors());
    }

    public function testCopyLogKeepsSameValue(): void
    {
        $logger = $this->make('test-log', false);
        $logger->copyLog();
        $this->assertEquals('test-log', $logger->getLog());
    }
}
