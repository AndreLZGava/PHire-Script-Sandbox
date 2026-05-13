<?php

namespace Sandbox\Samples\success\case_15;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('variables')]
#[Tag('bool')]
#[Tag('literal')]
#[Tag('casting')]
#[Tag('reference')]
#[Documentation(true)]
#[Description('This compiles Bool variable declarations with literal, cast and reference')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/VariablesBool.ps → src/compiled/VariablesBool.php",
            "[Copied]: src/output/VariablesBool.psc → src/compiled/VariablesBool.psc",
        ]);
    }
}
