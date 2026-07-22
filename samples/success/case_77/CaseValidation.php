<?php

namespace Sandbox\Samples\success\case_77;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Description;

#[Description('Attribute declarations and usages')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute(): void
    {
        $this->assertHasMessage([
            '✔ src/output/Entity.ps → src/compiled/Entity.php',
            '✔ src/output/Field.ps → src/compiled/Field.php',
            '✔ src/output/User.ps → src/compiled/User.php',
        ]);
    }

    public function executeTest(): void
    {
        $entity = file_get_contents($this->getOutputPath('Entity.php'));
        $this->assertTrue(str_contains($entity, '#[\Attribute]'));
        $this->assertTrue(str_contains($entity, 'class Entity'));
        $this->assertTrue(str_contains($entity, 'public string $name'));

        $field = file_get_contents($this->getOutputPath('Field.php'));
        $this->assertTrue(str_contains($field, '#[\Attribute]'));
        $this->assertTrue(str_contains($field, 'class Field'));
        $this->assertTrue(str_contains($field, 'public string $name'));
        $this->assertTrue(str_contains($field, 'public string $type'));
        $this->assertTrue(str_contains($field, 'null|int $min'));
        $this->assertTrue(str_contains($field, 'null|int $max'));

        $user = file_get_contents($this->getOutputPath('User.php'));
        $this->assertTrue(str_contains($user, "#[Entity('User')]"));
        $this->assertTrue(str_contains($user, 'class User'));
        $this->assertTrue(str_contains($user, "#[Field(name: 'name', type: 'String', min: 3, max: 255)]"));
        $this->assertTrue(str_contains($user, "#[Field(name: 'lastName', type: 'String', min: 3, max: 255)]"));
        $this->assertTrue(str_contains($user, "#[Field(name: 'born', type: 'Date')]"));
        $this->assertTrue(str_contains($user, 'public string $name'));
        $this->assertTrue(str_contains($user, 'public string $lastName'));
        $this->assertTrue(str_contains($user, 'public Date $born'));
    }
}
