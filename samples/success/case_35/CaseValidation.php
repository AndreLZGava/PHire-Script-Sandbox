<?php

namespace Sandbox\Samples\success\case_35;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;

#[Description('Arrow functions with zero and one typed parameter compile correctly')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->stopIfNoTest = false;
        $this->assertHasMessage([
            "✔ src/output/ArrowFunctions.phs → src/compiled/ArrowFunctions.php",
        ]);
    }
}
