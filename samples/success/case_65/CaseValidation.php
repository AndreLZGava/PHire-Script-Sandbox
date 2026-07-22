<?php


namespace Sandbox\Samples\success\case_65;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;

#[Description('Math TypeMethods on Float and Int: root, log, logBase, round, floor, ceil')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute(): void
    {
        $this->assertHasMessage([
            '✔ src/output/MathTypeMethods.phs',
        ]);
    }

    public function executeTest(): void
    {
        $output = file_get_contents($this->getOutputPath('MathTypeMethods.php'));

        $this->assertTrue(str_contains($output, '** (1.0 / 3)'));
        $this->assertTrue(str_contains($output, '\log($myFloat)'));
        $this->assertTrue(str_contains($output, '\log($myFloat, 2)'));
        $this->assertTrue(str_contains($output, '\round($myInt)'));
        $this->assertTrue(str_contains($output, '\floor($myInt)'));
        $this->assertTrue(str_contains($output, '\ceil($myInt)'));
    }
}
