<?php


namespace Sandbox\Samples\success\case_61;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;

#[Description('Arithmetic operators in assignments and return statements')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute(): void
    {
        $this->assertHasMessage([
            '✔ src/output/Arithmetic.phs',
        ]);
    }

    public function executeTest(): void
    {
        $output = file_get_contents($this->getOutputPath('Arithmetic.php'));

        $this->assertTrue(str_contains($output, '$this->price + $this->discount'));
        $this->assertTrue(str_contains($output, '$this->price * 1.1'));
        $this->assertTrue(str_contains($output, '$this->price - $this->discount'));
        $this->assertTrue(str_contains($output, '$this->price / 2'));
        $this->assertTrue(str_contains($output, '$this->count % 3'));
        $this->assertTrue(str_contains($output, '$this->price ** 2'));
        $this->assertTrue(str_contains($output, 'return $total'));
        $this->assertTrue(str_contains($output, 'return $result'));
    }
}
