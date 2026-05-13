<?php

namespace Sandbox\Samples\success\case_24;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('variables')]
#[Tag('super-type')]
#[Tag('slug')]
#[Documentation(true)]
#[Description('This compiles Slug super type variable declaration and reference')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/VariablesSuperTypeSlug.ps → src/compiled/VariablesSuperTypeSlug.php",
            "[Copied]: src/output/VariablesSuperTypeSlug.psc → src/compiled/VariablesSuperTypeSlug.psc",
        ]);
    }
}
