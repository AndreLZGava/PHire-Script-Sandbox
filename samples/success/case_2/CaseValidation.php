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
            "✔ src/output/UserCredentials.phs → src/compiled/UserCredentials.php",
            "✔ src/output/Authenticator.phs → src/compiled/Authenticator.php",
            "[Copied]: src/output/Authenticator.phc → src/compiled/Authenticator.phc",
            "✔ src/output/Another.interface.phs → src/compiled/Another.interface.php",
        ]);
    }
}
