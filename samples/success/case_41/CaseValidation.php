<?php

namespace Sandbox\Samples\success\case_41;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('external')]
#[Tag('static-method')]
#[Tag('instance-method')]
#[Tag('type-propagation')]
#[Documentation(true)]
#[Description('External class: static method, instance method on class name, and type propagation for chained calls')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/ExternalCallingStaticMethods.phs → src/compiled/ExternalCallingStaticMethods.php",
        ]);
    }
}
