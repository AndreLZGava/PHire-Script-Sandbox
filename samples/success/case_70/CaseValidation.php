<?php

namespace Sandbox\Samples\success\case_70;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;

#[Description('Collection type declarations (Map, Queue, Stack, List) compile to [] and array.add() compiles to $arr[] = value')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/Collections.phs → src/compiled/Collections.php",
        ]);
    }
}
