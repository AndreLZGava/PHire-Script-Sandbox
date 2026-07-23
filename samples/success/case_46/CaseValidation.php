<?php

namespace Sandbox\Samples\success\case_46;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('method-chaining')]
#[Tag('string')]
#[Tag('safe-navigation')]
#[Documentation(true)]
#[Description('Safe navigation operator ?. propagates null — between()?.length() emits null guard in PHP')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/SafeNavigation.phs → src/compiled/SafeNavigation.php",
        ]);
    }
}
