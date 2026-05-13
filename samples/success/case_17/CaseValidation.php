<?php

namespace Sandbox\Samples\success\case_17;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('variables')]
#[Tag('int')]
#[Tag('literal')]
#[Tag('casting')]
#[Tag('reference')]
#[Documentation(true)]
#[Description('This compiles Int variable declarations with literal, cast and reference')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/VariablesInt.ps → src/compiled/VariablesInt.php",
            "[Copied]: src/output/VariablesInt.psc → src/compiled/VariablesInt.psc",
        ]);
    }
}
