<?php

namespace Sandbox\Samples\success\case_71;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;

#[Description('Arrow functions without this reference are emitted with static prefix')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/ArrowFunctionNoThis.phs → src/compiled/ArrowFunctionNoThis.php",
        ]);
    }
}
