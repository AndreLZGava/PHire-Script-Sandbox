<?php

namespace Sandbox\Samples\success\case_81;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;

#[Description('Exception with typed properties and auto-generated readonly constructor')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute(): void
    {
        $this->assertHasMessage([
            '✔ src/output/Exceptions.ps → src/compiled/Exceptions.php',
        ]);
    }

    public function executeTest(): void
    {
        $output = file_get_contents($this->getOutputPath('Exceptions.php'));

        $this->assertTrue(str_contains($output, 'class ValidationException extends \Exception'));
        $this->assertTrue(str_contains($output, 'public readonly string $field'));
        $this->assertTrue(str_contains($output, 'public readonly string $reason'));
        $this->assertTrue(str_contains($output, 'string $message'));
        $this->assertTrue(str_contains($output, 'int $code'));
        $this->assertTrue(str_contains($output, 'public readonly array $context'));
        $this->assertTrue(str_contains($output, 'parent::__construct($message, $code, $previous)'));
    }
}
