<?php


namespace Sandbox\Samples\success\case_1;

use PHireScript\Helper\Debug\Debug;
use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('interface')]
#[Tag('file-name-compost')]
#[Tag('package')]
#[Tag('methods')]
#[Documentation(true)]
#[Description('This compiles interface, when file name is compost!')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "[Copied]: src/output/Another.interface.phc  →  src/compiled/Another.interface.phc",
            "✔ src/output/Another.interface.phs  →  src/compiled/Another.interface.php",
        ]);
    }
}
