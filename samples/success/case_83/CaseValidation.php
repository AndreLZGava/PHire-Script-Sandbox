<?php

namespace Sandbox\Samples\success\case_83;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;

#[Description('Exception message template interpolation compiles to compile-time sprintf in constructor')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute(): void
    {
        $this->assertHasMessage([
            '✔ src/output/FieldException.phs → src/compiled/FieldException.php',
        ]);
    }

    public function executeTest(): void
    {
        $output = file_get_contents($this->getOutputPath('FieldException.php'));

        $this->assertTrue(str_contains($output, 'class FieldException extends \Exception'));
        $this->assertTrue(str_contains($output, 'public readonly string $field'));
        $this->assertTrue(str_contains($output, "if (\$message === '')"), 'message check must be present');
        $this->assertTrue(str_contains($output, "sprintf('Invalid field: %s'"), 'sprintf must be generated');
        $this->assertTrue(str_contains($output, 'parent::__construct($message, $code, $previous)'));
    }
}
