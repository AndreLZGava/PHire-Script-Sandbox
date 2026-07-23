<?php

namespace Sandbox\Samples\success\case_48;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('method-chaining')]
#[Tag('string')]
#[Tag('array')]
#[Tag('cross-type')]
#[Documentation(true)]
#[Description('Cross-type method chaining: String.split() returns Array, then Array.length() returns Int')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/CrossTypeChain.phs → src/compiled/CrossTypeChain.php",
        ]);
    }
}
