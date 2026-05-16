<?php

use PHPUnit\Framework\TestCase;

class ElseIfTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $vars = [];

    private string $source = '';

    protected function setUp(): void
    {
        ob_start();
        include __DIR__ . '/ElseIf.php';
        ob_end_clean();
        $this->vars = get_defined_vars();
        $this->source = (string) file_get_contents(__DIR__ . '/ElseIf.php');
    }

    public function testScoreOf75GivesCGrade(): void
    {
        // score = 75, matches elseif(score >= 70)
        $this->assertArrayHasKey('grade', $this->vars);
        $this->assertEquals('C', $this->vars['grade']);
    }

    public function testCompiledFileContainsElseif(): void
    {
        $this->assertStringContainsString('elseif (', $this->source);
    }

    public function testCompiledFileContainsElse(): void
    {
        $this->assertStringContainsString('} else {', $this->source);
    }

    public function testCompiledFileContainsAllGrades(): void
    {
        $this->assertStringContainsString("'A'", $this->source);
        $this->assertStringContainsString("'B'", $this->source);
        $this->assertStringContainsString("'C'", $this->source);
        $this->assertStringContainsString("'F'", $this->source);
    }

    public function testCompiledFileContainsGteOperator(): void
    {
        $this->assertStringContainsString('>=', $this->source);
    }

    public function testFirstBranchNotTaken(): void
    {
        // score 75 < 90, so grade is not 'A'
        $this->assertNotEquals('A', $this->vars['grade'] ?? null);
    }

    public function testSecondBranchNotTaken(): void
    {
        // score 75 < 80, so grade is not 'B'
        $this->assertNotEquals('B', $this->vars['grade'] ?? null);
    }

    public function testElseBranchNotTaken(): void
    {
        // score 75 >= 70, so else is not reached
        $this->assertNotEquals('F', $this->vars['grade'] ?? null);
    }
}
