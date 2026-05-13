<?php

namespace Sandbox\Samples\success\case_13;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('variables')]
#[Tag('object')]
#[Tag('float')]
#[Tag('assignment')]
#[Tag('reference')]
#[Documentation(true)]
#[Description('This compiles basic variable assignments including objects and floats')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/Variables.ps → src/compiled/Variables.php",
            "[Copied]: src/output/Variables.psc → src/compiled/Variables.psc",
        ]);
    }
}
