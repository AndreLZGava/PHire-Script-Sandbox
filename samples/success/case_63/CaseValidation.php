<?php


namespace Sandbox\Samples\success\case_63;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;

#[Description('Unary negation operators ! and -')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute(): void
    {
        $this->assertHasMessage([
            '✔ src/output/UnaryNegation.ps',
        ]);
    }

    public function executeTest(): void
    {
        $output = file_get_contents($this->getOutputPath('UnaryNegation.php'));

        $this->assertTrue(str_contains($output, '$inverted = !$this->flag'));
        $this->assertTrue(str_contains($output, '$negative = -$this->count'));
    }
}
