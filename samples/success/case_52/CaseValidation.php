<?php

namespace Sandbox\Samples\success\case_52;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('this')]
#[Tag('class')]
#[Tag('property-assignment')]
#[Documentation(true)]
#[Description('this.property assignment and read across multiple methods in a class')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/SafeLogger.phs → src/compiled/SafeLogger.php",
        ]);
    }
}
