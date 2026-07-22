<?php

namespace Sandbox\Samples\success\case_45;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('method-chaining')]
#[Tag('string')]
#[Tag('literal')]
#[Documentation(true)]
#[Description('Method chaining on string literals: length, toUpperCase, replace called directly on quoted strings')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/LiteralChain.phs → src/compiled/LiteralChain.php",
        ]);
    }
}
