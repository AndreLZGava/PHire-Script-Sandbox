<?php

namespace Sandbox\Samples\success\case_16;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('variables')]
#[Tag('float')]
#[Tag('literal')]
#[Tag('casting')]
#[Tag('reference')]
#[Documentation(true)]
#[Description('This compiles Float variable declarations with literal, cast and reference')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/VariablesFloat.phs → src/compiled/VariablesFloat.php",
            "[Copied]: src/output/VariablesFloat.phc → src/compiled/VariablesFloat.phc",
        ]);
    }
}
