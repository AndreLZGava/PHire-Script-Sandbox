<?php


namespace Sandbox\Samples\success\case_3;

use PHireScript\Helper\Debug\Debug;
use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('class')]
#[Tag('package')]
#[Tag('magic-methods')]
#[Tag('methods')]
#[Documentation(true)]
#[Description('This compiles class with all magic methods')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/MagicMethods.ps → src/compiled/MagicMethods.php",
        ]);
    }
}
