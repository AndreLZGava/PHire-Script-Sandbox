<?php

namespace Sandbox\Samples\error\case_50;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('this')]
#[Tag('checker')]
#[Tag('error')]
#[Documentation(true)]
#[Description("'this' at top level outside any class must produce CheckerException")]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "'this' is not valid outside",
        ]);
    }
}
