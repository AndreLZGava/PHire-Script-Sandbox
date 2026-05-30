<?php

namespace Sandbox\Samples\success\case_39;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('external')]
#[Tag('static-method')]
#[Tag('constant')]
#[Tag('instance-method')]
#[Documentation(true)]
#[Description('External class: static method call, constant access, and instance method call on DateTime with alias')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/ExternalCallingConstants.ps → src/compiled/ExternalCallingConstants.php",
        ]);
    }
}
