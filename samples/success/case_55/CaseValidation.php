<?php

namespace Sandbox\Samples\success\case_55;

use PHireScript\Orchestrator\AbstractCaseValidation;

class CaseValidation extends AbstractCaseValidation
{
    public function execute(): void
    {
        $this->assertHasMessage([
            '✔ src/output/Getter.phs',
        ]);
    }

    public function executeTest(): void
    {
        $output = file_get_contents($this->getOutputPath('Getter.php'));

        $this->assertTrue(str_contains($output, 'public function getId(): int'));
        $this->assertTrue(str_contains($output, 'return $this->id;'));
        $this->assertTrue(str_contains($output, 'public function getName(): string'));
        $this->assertTrue(str_contains($output, 'return $this->name;'));
        $this->assertTrue(str_contains($output, 'public function getActive(): bool'));
        $this->assertTrue(str_contains($output, 'return $this->active;'));
        $this->assertFalse(str_contains($output, 'function setId'));
        $this->assertFalse(str_contains($output, 'function setName'));
        $this->assertFalse(str_contains($output, 'function setActive'));
    }
}
