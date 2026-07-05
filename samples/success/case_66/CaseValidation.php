<?php

use PHireScript\Orchestrator\Validation\AbstractCaseValidation;

class CaseValidation extends AbstractCaseValidation
{
    public function execute(): void
    {
        $this->assertCompilesSuccessfully();
    }

    public function executeTest(): void
    {
        $php = file_get_contents($this->getCompiledPath('Person.php'));

        $this->assertTrue(
            str_contains($php, 'public function setName(string $n): void'),
            'setName method must accept param $n with void return'
        );
        $this->assertTrue(
            str_contains($php, '$this->name = $n;'),
            'setName body must assign param $n to $this->name'
        );
        $this->assertTrue(
            str_contains($php, 'public function setAge(int $a): void'),
            'setAge method must accept param $a with void return'
        );
        $this->assertTrue(
            str_contains($php, '$this->age = $a;'),
            'setAge body must assign param $a to $this->age'
        );
        $this->assertTrue(
            str_contains($php, 'public function greet(string $greeting): string'),
            'greet method must accept param $greeting'
        );
        $this->assertTrue(
            str_contains($php, 'return $greeting;'),
            'greet body must return param $greeting'
        );
    }
}
