<?php

namespace Sandbox\Samples\success\case_54;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('this')]
#[Tag('self-return')]
#[Tag('fluent')]
#[Documentation(true)]
#[Description('Self return type compiles to : static; return this compiles to return $this — fluent builder pattern')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/Builder.phs → src/compiled/Builder.php",
        ]);
    }
}
