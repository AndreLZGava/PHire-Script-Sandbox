<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/StatusChecker.php';

class StatusCheckerTest extends TestCase
{
    private string $fqcn = 'PHireScript\Sandbox\src\output\StatusChecker';

    private function make(bool $active = true, string $status = 'active'): object
    {
        return new $this->fqcn($active, $status);
    }

    public function testGetStatusReturnsInitialStatus(): void
    {
        $checker = $this->make(true, 'active');
        $this->assertEquals('active', $checker->getStatus());
    }

    public function testToggleChangesStatusToInactiveWhenActive(): void
    {
        $checker = $this->make(true, 'active');
        $checker->toggle();
        $this->assertEquals('inactive', $checker->getStatus());
    }

    public function testToggleChangesStatusToActiveWhenInactive(): void
    {
        $checker = $this->make(false, 'inactive');
        $checker->toggle();
        $this->assertEquals('active', $checker->getStatus());
    }

    public function testIsActiveReturnsTrueWhenActive(): void
    {
        $checker = $this->make(true, 'active');
        $this->assertTrue($checker->isActive());
    }

    public function testIsActiveReturnsFalseWhenInactive(): void
    {
        $checker = $this->make(false, 'inactive');
        $this->assertFalse($checker->isActive());
    }
}
