<?php


namespace Sandbox\Samples\success\case_2;

use PHireScript\Helper\Debug\Debug;
use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('interface')]
#[Tag('package')]
#[Tag('use')]
#[Tag('extends')]
#[Tag('methods-with-using-params')]
#[Documentation(true)]
#[Description('This compiles interface, with complex methods!')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/UserCredentials.ps → src/compiled/UserCredentials.php",
            "✔ src/output/Authenticator.ps → src/compiled/Authenticator.php",
            "[Copied]: src/output/Authenticator.psc → src/compiled/Authenticator.psc",
            "✔ src/output/Another.interface.ps → src/compiled/Another.interface.php",
        ]);
    }
}
