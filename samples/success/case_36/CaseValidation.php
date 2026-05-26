<?php

namespace Sandbox\Samples\success\case_36;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;

#[Description('Arrow functions with multiple parameters and union types compile correctly')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->stopIfNoTest = false;
        $this->assertHasMessage([
            "✔ src/output/ArrowFunctionsMultiParam.ps → src/compiled/ArrowFunctionsMultiParam.php",
        ]);
    }
}
