<?php

namespace Sandbox\Samples\success\case_26;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('variables')]
#[Tag('super-type')]
#[Tag('uuid')]
#[Documentation(true)]
#[Description('This compiles Uuid super type generation and reference')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/VariablesSuperTypeUuid.ps → src/compiled/VariablesSuperTypeUuid.php",
            "[Copied]: src/output/VariablesSuperTypeUuid.psc → src/compiled/VariablesSuperTypeUuid.psc",
        ]);
    }
}
