<?php

namespace Sandbox\Samples\success\case_25;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('variables')]
#[Tag('super-type')]
#[Tag('url')]
#[Documentation(true)]
#[Description('This compiles Url super type variable declaration and reference')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/VariablesSuperTypeUrl.ps → src/compiled/VariablesSuperTypeUrl.php",
            "[Copied]: src/output/VariablesSuperTypeUrl.psc → src/compiled/VariablesSuperTypeUrl.psc",
        ]);
    }
}
