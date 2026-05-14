<?php


namespace Sandbox\Samples\success\case_30;

use PHireScript\Helper\Debug\Debug;
use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('type')]
#[Tag('class')]
#[Tag('package')]
#[Tag('default-constructor')]
#[Tag('super-type')]
#[Tag('email')]
#[Documentation(true)]
#[Description('This compiles type User with Email super type and scoped scope')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/User.ps → src/compiled/User.php",
        ]);
    }
}
