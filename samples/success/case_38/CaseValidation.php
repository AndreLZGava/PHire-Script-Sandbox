<?php

namespace Sandbox\Samples\success\case_38;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;

#[Description('Arrow functions automatically capture external variables with use()')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->stopIfNoTest = false;
        $this->assertHasMessage([
            "✔ src/output/ArrowFunctionCapture.phs → src/compiled/ArrowFunctionCapture.php",
        ]);
    }
}
