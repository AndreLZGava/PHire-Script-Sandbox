<?php


namespace Sandbox\Samples\success\case_6;

use PHireScript\Helper\Debug\Debug;
use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('immutalbe')]
#[Tag('class')]
#[Tag('package')]
#[Tag('simple-dto')]
#[Documentation(true)]
#[Description('This compiles immutable User')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->stopIfNoTest = true;
        $this->assertHasMessage([
            "✔ src/output/UserImmutable.ps → src/compiled/UserImmutable.php",
        ]);
    }
}
