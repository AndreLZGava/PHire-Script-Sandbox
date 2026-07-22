<?php

namespace Sandbox\Samples\success\case_73;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;

#[Description('Generated PHP files include declare(strict_types=1) header after <?php')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/Greeter.phs → src/compiled/Greeter.php",
        ]);
    }

    public function rightAfterFirstExecution()
    {
        $compiledFile = __DIR__ . '/../../../src/compiled/Greeter.php';

        if (file_exists($compiledFile)) {
            $content = file_get_contents($compiledFile);
            $this->assertTrue(
                str_contains($content, "strict_types=1"),
                "Generated PHP must contain declare(strict_types=1);"
            );
        }
    }
}
