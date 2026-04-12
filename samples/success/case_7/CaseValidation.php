<?php


namespace Sandbox\Samples\success\case_7;

use PHireScript\Helper\Debug\Debug;
use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('interface')]
#[Tag('package')]
#[Documentation(false)]
#[Description('This compiles usert User')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->stopIfNoTest = true;
        $this->assertHasMessage([
            "✔ src/output/UserInterface.ps -> src/compiled/UserInterface.php",
        ]);
    }
}
