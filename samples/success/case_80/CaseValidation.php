<?php

namespace Sandbox\Samples\success\case_80;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;

#[Description('Exception bare declaration and inheritance')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute(): void
    {
        $this->assertHasMessage([
            '✔ src/output/AppException.phs → src/compiled/AppException.php',
            '✔ src/output/NotFoundException.phs → src/compiled/NotFoundException.php',
        ]);
    }

    public function executeTest(): void
    {
        $appEx = file_get_contents($this->getOutputPath('AppException.php'));
        $this->assertTrue(str_contains($appEx, 'class AppException extends \Exception'));
        $this->assertTrue(!str_contains($appEx, '__construct'), 'Bare exception must not have a constructor');

        $notFound = file_get_contents($this->getOutputPath('NotFoundException.php'));
        $this->assertTrue(str_contains($notFound, 'class NotFoundException extends AppException'));
        $this->assertTrue(!str_contains($notFound, '__construct'), 'Bare exception must not have a constructor');
    }
}
