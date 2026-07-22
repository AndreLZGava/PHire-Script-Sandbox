<?php

namespace Sandbox\Samples\success\case_78;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;

#[Description('PHP built-in attributes and multiple attributes per target')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute(): void
    {
        $this->assertHasMessage([
            '✔ src/output/Auditable.ps → src/compiled/Auditable.php',
            '✔ src/output/Other.ps → src/compiled/Other.php',
        ]);
    }

    public function executeTest(): void
    {
        $auditable = file_get_contents($this->getOutputPath('Auditable.php'));
        $this->assertTrue(str_contains($auditable, '#[\Attribute]'));
        $this->assertTrue(str_contains($auditable, 'class Auditable'));
        $this->assertTrue(str_contains($auditable, 'public string $reason'));

        $code = file_get_contents($this->getOutputPath('Other.php'));

        // built-in on class
        $this->assertTrue(str_contains($code, '#[\AllowDynamicProperties]'));

        // multiple attributes on same property: custom + built-in
        $this->assertTrue(str_contains($code, "#[Auditable(reason: 'legacy field')]"));
        $this->assertTrue(str_contains($code, '#[\Deprecated]'));
        $this->assertTrue(str_contains($code, 'public Date $oldPassword'));

        // built-in on method
        $this->assertTrue(str_contains($code, '#[\ReturnTypeWillChange]'));
        $this->assertTrue(str_contains($code, 'public function convertOldPasswordToNewOne(): string'));
    }
}
