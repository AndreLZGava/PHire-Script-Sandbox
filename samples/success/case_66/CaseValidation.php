<?php


namespace Sandbox\Samples\success\case_66;

use PHireScript\Orchestrator\AbstractCaseValidation;

class CaseValidation extends AbstractCaseValidation
{
    public function execute(): void
    {
        $this->assertHasMessage([
            '✔ src/output/Person.ps',
        ]);
    }

    public function executeTest(): void
    {
        $php = file_get_contents($this->getOutputPath('Person.php'));

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
