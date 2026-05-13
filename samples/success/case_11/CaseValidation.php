<?php

namespace Sandbox\Samples\success\case_11;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('primitives')]
#[Tag('variables')]
#[Tag('casting')]
#[Tag('inference')]
#[Tag('string')]
#[Tag('int')]
#[Tag('float')]
#[Tag('bool')]
#[Tag('array')]
#[Tag('object')]
#[Documentation(true)]
#[Description('This compiles all primitive types with inference and explicit casting')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/Primitives.ps → src/compiled/Primitives.php",
            "[Copied]: src/output/Primitives.psc → src/compiled/Primitives.psc",
        ]);
    }
}
