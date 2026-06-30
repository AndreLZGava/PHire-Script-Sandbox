<?php

use PHireScript\Sandbox\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;

#[Description('Grouped expressions with parentheses')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute(): void
    {
        $this->assertHasMessage([
            '✔ src/output/GroupedExpressions.ps',
        ]);
    }

    public function executeTest(): void
    {
        $output = file_get_contents($this->getOutputPath('GroupedExpressions.php'));

        $this->assertTrue(str_contains($output, '($this->a + $this->b) * $this->c'));
    }
}
