<?php

use PHireScript\Sandbox\Orchestrator\AbstractCaseValidation;

class CaseValidation extends AbstractCaseValidation
{
    public function execute(): void
    {
        $this->assertHasMessage([
            'OverrideTest.ps',
        ]);
    }

    public function executeTest(): void
    {
        $output = file_get_contents($this->getOutputPath('OverrideTest.php'));

        $this->assertTrue(str_contains($output, 'public function getId(): int'));
        // Only one getId — the explicit method, not duplicated with generated getter
        $this->assertSame(1, substr_count($output, 'function getId'));
    }
}
