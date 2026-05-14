<?php


namespace Sandbox\Samples\success\case_28;

use PHireScript\Helper\Debug\Debug;
use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('class')]
#[Tag('trait')]
#[Tag('interface')]
#[Tag('package')]
#[Tag('methods')]
#[Tag('singleton')]
#[Documentation(true)]
#[Description('This compiles class with trait, multiple interfaces and diverse return types')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/AuthenticatorClass.ps → src/compiled/AuthenticatorClass.php",
        ]);
    }
}
