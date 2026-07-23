<?php


namespace Sandbox\Samples\success\case_57;

use PHireScript\Orchestrator\AbstractCaseValidation;

class CaseValidation extends AbstractCaseValidation
{
    public function execute(): void
    {
        $this->assertHasMessage([
            '✔ src/output/Combined.phs',
        ]);
    }

    public function executeTest(): void
    {
        $output = file_get_contents($this->getOutputPath('Combined.php'));

        $this->assertTrue(str_contains($output, 'public function getUsername(): string'));
        $this->assertTrue(str_contains($output, 'public function setUsername(string $username): void'));
        $this->assertTrue(str_contains($output, 'public function getCount(): int'));
        $this->assertTrue(str_contains($output, 'public function setCount(int $count): void'));
        $this->assertTrue(str_contains($output, 'public function getActive(): bool'));
        $this->assertTrue(str_contains($output, 'public function setActive(bool $active): void'));
    }
}
