<?php

namespace Sandbox\Samples\success\case_69;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;

#[Description('String variable declarations: literal, cast, reference, join and getClass compile correctly')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/VariablesString.ps → src/compiled/VariablesString.php",
        ]);
    }
}
