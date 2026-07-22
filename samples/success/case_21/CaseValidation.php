<?php

namespace Sandbox\Samples\success\case_21;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('variables')]
#[Tag('super-type')]
#[Tag('duration')]
#[Documentation(true)]
#[Description('This compiles Duration super type variable declaration and reference')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/VariablesSuperTypeDuration.phs → src/compiled/VariablesSuperTypeDuration.php",
            "[Copied]: src/output/VariablesSuperTypeDuration.phc → src/compiled/VariablesSuperTypeDuration.phc",
        ]);
    }
}
