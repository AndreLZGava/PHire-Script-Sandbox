<?php

namespace Sandbox\Samples\success\case_27;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('types')]
#[Tag('snapshot')]
#[Tag('test')]
#[Documentation(true)]
#[Description('Snapshot and disabled test file for types validation')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "[Copied]: src/output/TypesTest.psc → src/compiled/TypesTest.psc",
            "[Copied]: src/output/TypesTest.psX → src/compiled/TypesTest.psX",
        ]);
    }
}
