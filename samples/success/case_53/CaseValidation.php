<?php

namespace Sandbox\Samples\success\case_53;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('this')]
#[Tag('arrow-function')]
#[Tag('class')]
#[Documentation(true)]
#[Description('this.property inside an arrow function defined within a class method')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/Mapper.phs → src/compiled/Mapper.php",
        ]);
    }
}
