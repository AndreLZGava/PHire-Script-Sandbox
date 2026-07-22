<?php

namespace Sandbox\Samples\success\case_43;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('method-chaining')]
#[Tag('string')]
#[Tag('auto-assignment')]
#[Documentation(true)]
#[Description('Method chaining with self-assignment: mystring = mystring.toUpperCase() overwrites the variable')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/AutoAssignment.phs → src/compiled/AutoAssignment.php",
        ]);
    }
}
