<?php

namespace Sandbox\Samples\success\case_10;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('constants')]
#[Tag('global-constant')]
#[Documentation(true)]
#[Description('This compiles global constant assignment')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/Constants.phs → src/compiled/Constants.php",
            "[Copied]: src/output/Constants.phc → src/compiled/Constants.phc",
        ]);
    }
}
