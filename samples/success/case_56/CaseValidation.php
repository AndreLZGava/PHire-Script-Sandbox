<?php

use PHireScript\Sandbox\Orchestrator\AbstractCaseValidation;

class CaseValidation extends AbstractCaseValidation
{
    public function execute(): void
    {
        $this->assertHasMessage([
            'Setter.ps',
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
