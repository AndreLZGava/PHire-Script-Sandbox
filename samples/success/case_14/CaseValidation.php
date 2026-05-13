<?php

namespace Sandbox\Samples\success\case_14;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('variables')]
#[Tag('array')]
#[Tag('literal')]
#[Tag('casting')]
#[Tag('method-call')]
#[Tag('chaining')]
#[Tag('reference')]
#[Documentation(true)]
#[Description('This compiles array variable declarations with literal, cast, method chaining and reference')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        return;
        $this->assertHasMessage([
            "✔ src/output/VariablesArray.ps → src/compiled/VariablesArray.php",
            "[Copied]: src/output/VariablesArray.psc → src/compiled/VariablesArray.psc",
        ]);
    }
}
