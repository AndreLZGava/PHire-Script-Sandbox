<?php

use PHireScript\Sandbox\Orchestrator\AbstractCaseValidation;

class CaseValidation extends AbstractCaseValidation
{
    public function execute(): void
    {
        $this->assertHasMessage([
            'ExampleGetterSetterClass.ps',
        ]);
    }

    public function executeTest(): void
    {
        $output = file_get_contents($this->getOutputPath('ExampleGetterSetterClass.php'));

        $this->assertTrue(str_contains($output, 'public function getId(): int'));
        $this->assertTrue(str_contains($output, 'public function setEmail(string $email): void'));
        $this->assertTrue(str_contains($output, 'public function getUsername(): string'));
        $this->assertTrue(str_contains($output, 'public function setUsername(string $username): void'));
        $this->assertTrue(str_contains($output, 'private function getIsAdmin(): bool'));
        $this->assertTrue(str_contains($output, 'protected function setIsAdmin(bool $isAdmin): void'));
        $this->assertTrue(str_contains($output, 'protected function getMetadata(): array'));
        $this->assertTrue(str_contains($output, 'private function setMetadata(array $metadata): void'));
        $this->assertTrue(str_contains($output, 'private array $metadata'));
    }
}
