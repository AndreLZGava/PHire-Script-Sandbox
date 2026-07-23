<?php

namespace Sandbox\Samples\success\case_40;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('external')]
#[Tag('instantiation')]
#[Tag('instance-method')]
#[Documentation(true)]
#[Description('External class: instantiation with new ClassName() and instance method calls on the resulting variable')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/ExternalCallingChainningMethods.phs → src/compiled/ExternalCallingChainningMethods.php",
        ]);
    }
}
