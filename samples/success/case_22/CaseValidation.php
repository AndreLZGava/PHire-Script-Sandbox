<?php

namespace Sandbox\Samples\success\case_22;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('variables')]
#[Tag('super-type')]
#[Tag('email')]
#[Documentation(true)]
#[Description('This compiles Email super type variable declaration and reference')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/VariablesSuperTypeEmail.ps → src/compiled/VariablesSuperTypeEmail.php",
            "[Copied]: src/output/VariablesSuperTypeEmail.psc → src/compiled/VariablesSuperTypeEmail.psc",
        ]);
    }
}
