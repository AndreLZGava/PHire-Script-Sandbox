<?php

namespace Sandbox\Samples\success\case_42;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('method-chaining')]
#[Tag('string')]
#[Tag('chain')]
#[Documentation(true)]
#[Description('Method chaining on String variables: replace, toUpperCase, length in 2 and 3-method chains')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/StringChain.ps → src/compiled/StringChain.php",
        ]);
    }
}
