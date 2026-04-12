<?php


namespace Sandbox\Samples\success\case_9;

use PHireScript\Helper\Debug\Debug;
use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('try-handle')]
#[Tag('try-handle-always')]
#[Tag('super-types')]
#[Documentation(true)]
#[Description('This compiles try/handle/always to try/catch/finally')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->stopIfNoTest = false;
        $this->assertHasMessage([
            "✔ src/output/TryHandleAlways.ps -> src/compiled/TryHandleAlways.php",
        ]);
    }
}
