<?php

namespace Sandbox\Samples\success\case_12;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('range')]
#[Tag('array')]
#[Tag('literal')]
#[Documentation(true)]
#[Description('This compiles range expressions inside array literals')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/Range.ps → src/compiled/Range.php",
            "[Copied]: src/output/Range.psc → src/compiled/Range.psc",
        ]);
    }
}
