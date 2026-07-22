<?php

namespace Sandbox\Samples\success\case_64;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;

#[Description('User-defined method calls as expression operands (BB-3 completion)')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute(): void
    {
        $this->assertHasMessage([
            '✔ src/output/Calculator.phs',
        ]);
    }

    public function executeTest(): void
    {
        $output = file_get_contents($this->getOutputPath('Calculator.php'));

        $this->assertTrue(str_contains($output, '$result = $this->getBase() * $this->getRate()'));
        $this->assertTrue(str_contains($output, '$result = ($this->getBase() + 10) * $this->getRate()'));
        $this->assertTrue(str_contains($output, 'private function getBase(): int'));
        $this->assertTrue(str_contains($output, 'private function getRate(): float'));
        $this->assertTrue(str_contains($output, 'private function total(): float'));
        $this->assertTrue(str_contains($output, 'private function withBonus(): float'));
    }
}
