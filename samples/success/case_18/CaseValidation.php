<?php

namespace Sandbox\Samples\success\case_18;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('variables')]
#[Tag('object')]
#[Tag('literal')]
#[Tag('casting')]
#[Tag('reference')]
#[Documentation(true)]
#[Description('This compiles Object variable declarations with inline literal, cast and reference')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/VariablesObject.ps → src/compiled/VariablesObject.php",
            "[Copied]: src/output/VariablesObject.psc → src/compiled/VariablesObject.psc",
        ]);
    }
}
