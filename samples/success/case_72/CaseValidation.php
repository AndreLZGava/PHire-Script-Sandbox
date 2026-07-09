<?php

namespace Sandbox\Samples\success\case_72;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;

#[Description('Arrow functions that reference this are emitted without static prefix')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/Formatter.ps → src/compiled/Formatter.php",
        ]);
    }
}
