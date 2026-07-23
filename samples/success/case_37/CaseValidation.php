<?php

namespace Sandbox\Samples\success\case_37;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;

#[Description('Arrow functions with default parameter values compile correctly')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->stopIfNoTest = false;
        $this->assertHasMessage([
            "✔ src/output/ArrowFunctionDefaults.phs → src/compiled/ArrowFunctionDefaults.php",
        ]);
    }
}
