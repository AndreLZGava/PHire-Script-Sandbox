<?php

namespace Sandbox\Samples\success\case_47;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('method-chaining')]
#[Tag('string')]
#[Tag('multi-line')]
#[Documentation(true)]
#[Description('Multi-line method chaining: dot at start of continuation line is treated as chain continuation')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/MultiLineChain.ps → src/compiled/MultiLineChain.php",
        ]);
    }
}
