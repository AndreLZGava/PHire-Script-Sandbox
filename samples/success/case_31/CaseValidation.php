<?php


namespace Sandbox\Samples\success\case_31;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('if')]
#[Tag('statement')]
#[Documentation(true)]
#[Description('This compiles a basic if statement with a variable assignment in the body')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/If.ps → src/compiled/If.php",
        ]);
    }
}
