<?php

namespace Sandbox\Samples\success\case_23;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('variables')]
#[Tag('super-type')]
#[Tag('json')]
#[Tag('array')]
#[Tag('object')]
#[Documentation(true)]
#[Description('This compiles Json super type from array, object and string inputs')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/VariablesSuperTypeJson.ps → src/compiled/VariablesSuperTypeJson.php",
            "[Copied]: src/output/VariablesSuperTypeJson.psc → src/compiled/VariablesSuperTypeJson.psc",
        ]);
    }
}
