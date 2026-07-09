<?php


namespace Sandbox\Samples\success\case_59;

use PHireScript\Orchestrator\AbstractCaseValidation;

class CaseValidation extends AbstractCaseValidation
{
    public function execute(): void
    {
        $this->assertHasMessage([
            '✔ src/output/ValueHolder.ps',
            '✔ src/output/Labeled.ps',
            '✔ src/output/Counter.ps',
        ]);
    }

    public function executeTest(): void
    {
        $valueHolder = file_get_contents($this->getOutputPath('ValueHolder.php'));
        $this->assertTrue(str_contains($valueHolder, 'function getValue()'));
        $this->assertFalse(str_contains($valueHolder, 'function setValue'));

        $labeled = file_get_contents($this->getOutputPath('Labeled.php'));
        $this->assertTrue(str_contains($labeled, 'trait Labeled'));
        $this->assertTrue(str_contains($labeled, 'function getLabel()'));

        $counter = file_get_contents($this->getOutputPath('Counter.php'));
        $this->assertTrue(str_contains($counter, 'function getCount(): int'));
        $this->assertTrue(str_contains($counter, 'function setCount(int $count): void'));
    }
}
