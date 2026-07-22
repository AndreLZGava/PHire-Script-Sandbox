<?php

namespace Sandbox\Samples\success\case_68;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;

#[Description('Arrow function with Float parameters and Float return type compiles correctly')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/ArrowFunctionFloat.phs → src/compiled/ArrowFunctionFloat.php",
        ]);
    }
}
