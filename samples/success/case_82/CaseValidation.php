<?php

namespace Sandbox\Samples\success\case_82;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;

#[Description('throw ExceptionType(namedArg: value) compiles to throw new ExceptionType(namedArg: $value)')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute(): void
    {
        $this->assertHasMessage([
            '✔ src/output/ValidationException.phs → src/compiled/ValidationException.php',
            '✔ src/output/UserService.phs → src/compiled/UserService.php',
        ]);
    }

    public function executeTest(): void
    {
        $output = file_get_contents($this->getOutputPath('UserService.php'));

        $this->assertTrue(str_contains($output, 'throw new ValidationException('), 'throw new must be emitted');
        $this->assertTrue(str_contains($output, 'field: $field'), 'named arg must be passed through');

        $exc = file_get_contents($this->getOutputPath('ValidationException.php'));
        $this->assertTrue(str_contains($exc, 'class ValidationException extends \Exception'));
        $this->assertTrue(str_contains($exc, 'public readonly string $field'));
    }
}
