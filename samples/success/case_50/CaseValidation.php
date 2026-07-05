<?php

namespace Sandbox\Samples\success\case_50;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('this')]
#[Tag('class')]
#[Documentation(true)]
#[Description('this.property and this.method() inside plain class methods and if blocks')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/Counter.ps → src/compiled/Counter.php",
        ]);
    }
}
