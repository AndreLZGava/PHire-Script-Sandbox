<?php

namespace Sandbox\Samples\success\case_19;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('variables')]
#[Tag('super-type')]
#[Tag('color')]
#[Documentation(true)]
#[Description('This compiles Color super type variable declaration and reference')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/VariablesSuperTypeColor.ps → src/compiled/VariablesSuperTypeColor.php",
            "[Copied]: src/output/VariablesSuperTypeColor.psc → src/compiled/VariablesSuperTypeColor.psc",
        ]);
    }
}
