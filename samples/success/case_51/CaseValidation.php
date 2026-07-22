<?php

namespace Sandbox\Samples\success\case_51;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('this')]
#[Tag('if')]
#[Tag('else')]
#[Documentation(true)]
#[Description('this inside if/else blocks inside class methods compiles to $this->')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/StatusChecker.phs → src/compiled/StatusChecker.php",
        ]);
    }
}
