<?php


namespace Sandbox\Samples\success\case_56;

use PHireScript\Orchestrator\AbstractCaseValidation;

class CaseValidation extends AbstractCaseValidation
{
    public function execute(): void
    {
        $this->assertHasMessage([
            '✔ src/output/Setter.ps',
        ]);
    }

    public function executeTest(): void
    {
        $output = file_get_contents($this->getOutputPath('Setter.php'));

        $this->assertTrue(str_contains($output, 'public function setEmail(string $email): void'));
        $this->assertTrue(str_contains($output, 'public function getUsername(): string'));
        $this->assertTrue(str_contains($output, 'public function setUsername(string $username): void'));
        $this->assertFalse(str_contains($output, 'function getEmail'));
    }
}
