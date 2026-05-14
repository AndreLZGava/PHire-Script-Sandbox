<?php


namespace Sandbox\Samples\success\case_32;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('if')]
#[Tag('else')]
#[Tag('statement')]
#[Documentation(true)]
#[Description('This compiles an if/else statement with variable assignment in each branch')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/IfElse.ps → src/compiled/IfElse.php",
        ]);
    }
}
