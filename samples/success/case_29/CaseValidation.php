<?php


namespace Sandbox\Samples\success\case_29;

use PHireScript\Helper\Debug\Debug;
use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('trait')]
#[Tag('package')]
#[Tag('methods')]
#[Documentation(true)]
#[Description('This compiles trait with a string return method')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/Logger.ps → src/compiled/Logger.php",
        ]);
    }
}
