<?php

namespace Sandbox\Samples\success\case_67;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('method-chaining')]
#[Tag('string')]
#[Tag('chain')]
#[Tag('assignment')]
#[Tag('return')]
#[Documentation(true)]
#[Description('Multi-call string chain in assignment and return contexts compiles without IIFE closures')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/ChainAssignment.phs → src/compiled/ChainAssignment.php",
        ]);
    }
}
