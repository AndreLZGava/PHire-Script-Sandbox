<?php


namespace Sandbox\Samples\success\case_5;

use PHireScript\Helper\Debug\Debug;
use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('type')]
#[Tag('class')]
#[Tag('package')]
#[Tag('default-constructor')]
#[Documentation(true)]
#[Description('This compiles type UserCredentials')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/UserCredentials.ps -> src/compiled/UserCredentials.php",
        ]);
    }
}
