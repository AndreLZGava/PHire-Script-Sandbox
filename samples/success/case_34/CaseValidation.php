<?php


namespace Sandbox\Samples\success\case_34;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('comparison')]
#[Tag('statement')]
#[Documentation(true)]
#[Description('This compiles elseif blocks with comparison operators in conditions')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->stopIfNoTest = false;
        $this->assertHasMessage([
            "✔ src/output/ElseIf.ps → src/compiled/ElseIf.php",
        ]);
    }
}
