<?php


namespace Sandbox\Samples\success\case_60;

use PHireScript\Orchestrator\AbstractCaseValidation;

class CaseValidation extends AbstractCaseValidation
{
    public function execute(): void
    {
        $this->assertHasMessage([
            '✔ src/output/OverrideTest.phs',
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
