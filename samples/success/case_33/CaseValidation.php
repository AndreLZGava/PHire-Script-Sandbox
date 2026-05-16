<?php


namespace Sandbox\Samples\success\case_33;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('comparison')]
#[Tag('statement')]
#[Documentation(true)]
#[Description('This compiles comparison operators (>, <, ==, ===, !=, !==, >=, <=) in if conditions')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->stopIfNoTest = false;
        $this->assertHasMessage([
            "✔ src/output/Comparison.ps → src/compiled/Comparison.php",
        ]);
    }
}
