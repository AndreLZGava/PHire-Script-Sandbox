<?php

namespace Sandbox\Samples\success\case_76;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;

#[Description('Multiple user-defined method calls as operands in complex expressions')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute(): void
    {
        $this->assertHasMessage([
            '✔ src/output/Pricing.phs',
        ]);
    }

    public function executeTest(): void
    {
        $output = file_get_contents($this->getOutputPath('Pricing.php'));

        $this->assertTrue(str_contains($output, '$this->getPrice() * (1 + $this->getTaxRate())'));
        $this->assertTrue(str_contains($output, '$this->getPrice() - $this->getPrice() * $this->getTaxRate()'));
        $this->assertTrue(str_contains($output, 'private function priceWithTax(): float'));
        $this->assertTrue(str_contains($output, 'private function discount(): float'));
    }
}
