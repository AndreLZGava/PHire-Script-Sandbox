<?php


namespace Sandbox\Samples\success\case_8;

use PHireScript\Helper\Debug\Debug;
use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('variables')]
#[Tag('super-types')]
#[Documentation(true)]
#[Description('This compiles multiple variables using default super types')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->stopIfNoTest = true;
        $this->assertHasMessage([
            "✔ src/output/MultipleSuperTypesVariables.ps → src/compiled/MultipleSuperTypesVariables.php",
        ]);
    }
}
