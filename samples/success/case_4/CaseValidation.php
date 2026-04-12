<?php


namespace Sandbox\Samples\success\case_4;

use PHireScript\Helper\Debug\Debug;
use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('abstract-class')]
#[Tag('class')]
#[Tag('package')]
#[Tag('abstract-property')]
#[Tag('methods')]
#[Documentation(true)]
#[Description('This compiles abstract class, with abstract property')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/Repository.ps → src/compiled/Repository.php",
        ]);
    }
}
