<?php

use PHPUnit\Framework\TestCase;

/**
 * Case 27 contains only snapshot (.psc) and disabled (.psX) files — no compilable .ps source.
 * TypesTest.psc is an intermediate snapshot showing array method chaining syntax
 * that the compiler is expected to support (List<T>, String methods).
 * TypesTest.psX is disabled and excluded from compilation.
 * These tests verify the files are correctly copied to the compiled output directory.
 */
class TypesSnapshotTest extends TestCase
{
    public function testSnapshotFileIsCopiedToCompiledDirectory(): void
    {
        $this->assertFileExists(__DIR__ . '/TypesTest.psc');
    }

    public function testDisabledFileIsCopiedToCompiledDirectory(): void
    {
        $this->assertFileExists(__DIR__ . '/TypesTest.psX');
    }

    public function testSnapshotFileIsNotEmpty(): void
    {
        $this->assertGreaterThan(0, filesize(__DIR__ . '/TypesTest.psc'));
    }

    public function testSnapshotFileStartsWithPhpTag(): void
    {
        $content = file_get_contents(__DIR__ . '/TypesTest.psc');
        $this->assertStringStartsWith('<?php', $content);
    }

    public function testSnapshotContainsExpectedVariables(): void
    {
        $content = file_get_contents(__DIR__ . '/TypesTest.psc');
        $this->assertStringContainsString('$fruits', $content);
        $this->assertStringContainsString('$user', $content);
        $this->assertStringContainsString('$counter', $content);
    }
}
