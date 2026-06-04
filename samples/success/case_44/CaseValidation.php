<?php

namespace Sandbox\Samples\success\case_44;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('method-chaining')]
#[Tag('string')]
#[Tag('expression')]
#[Documentation(true)]
#[Description('Method chaining used as expression: chain result assigned to variable, original variable unchanged')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/ChainInExpression.ps → src/compiled/ChainInExpression.php",
        ]);
    }
}
