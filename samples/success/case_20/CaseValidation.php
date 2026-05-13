<?php

namespace Sandbox\Samples\success\case_20;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('variables')]
#[Tag('super-type')]
#[Tag('cron')]
#[Documentation(true)]
#[Description('This compiles Cron super type variable declaration and reference')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/VariablesSuperTypeCron.ps → src/compiled/VariablesSuperTypeCron.php",
            "[Copied]: src/output/VariablesSuperTypeCron.psc → src/compiled/VariablesSuperTypeCron.psc",
        ]);
    }
}
