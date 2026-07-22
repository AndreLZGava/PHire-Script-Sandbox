<?php

namespace Sandbox\Samples\success\case_84;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;

#[Description('Native throw parameters: message, code, context, cause (→ previous)')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute(): void
    {
        $this->assertHasMessage([
            '✔ src/output/AppException.ps → src/compiled/AppException.php',
            '✔ src/output/DatabaseException.ps → src/compiled/DatabaseException.php',
            '✔ src/output/DataService.ps → src/compiled/DataService.php',
        ]);
    }

    public function executeTest(): void
    {
        $output = file_get_contents($this->getOutputPath('DataService.php'));

        // message: passed as explicit named arg
        $this->assertTrue(
            str_contains($output, "message: 'Explicit message override'"),
            'explicit message: must be passed through as named arg'
        );

        // code: passed as named arg
        $this->assertTrue(
            str_contains($output, 'code: 42'),
            'code: must be passed through'
        );

        // context: object literal emitted
        $this->assertTrue(
            str_contains($output, 'context:'),
            'context: must be present in throw'
        );

        // cause: must be remapped to previous:
        $this->assertTrue(
            str_contains($output, 'previous: $original'),
            'cause: must be remapped to previous:'
        );
        $this->assertFalse(
            str_contains($output, 'cause:'),
            'cause: keyword must not appear in PHP output'
        );
    }
}
