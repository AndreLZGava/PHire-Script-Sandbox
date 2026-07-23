<?php

namespace PHireScript\Sandbox\src\output;

use PHPUnit\Framework\TestCase;
use PHireScript\Runtime\Types\MetaTypes\Date;

require_once __DIR__ . '/Entity.php';
require_once __DIR__ . '/Field.php';
require_once __DIR__ . '/User.php';

class Case77Test extends TestCase
{
    private function reflect(string $class): \ReflectionClass
    {
        $fqcn = 'PHireScript\\Sandbox\\src\\output\\' . $class;

        if (!class_exists($fqcn)) {
            $this->fail("Class {$fqcn} does not exist");
        }

        return new \ReflectionClass($fqcn);
    }

    // --- Entity attribute class ---

    public function testEntityIsMarkedAsPhpAttribute(): void
    {
        $attrs = $this->reflect('Entity')->getAttributes(\Attribute::class);
        $this->assertCount(1, $attrs, 'Entity must carry #[\Attribute]');
    }

    public function testEntityConstructorAcceptsName(): void
    {
        $params = $this->reflect('Entity')->getConstructor()->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('name', $params[0]->getName());
        $this->assertSame('string', $params[0]->getType()->getName());
    }

    public function testEntityCanBeInstantiated(): void
    {
        $fqcn = 'PHireScript\\Sandbox\\src\\output\\Entity';
        $entity = new $fqcn('User');
        $this->assertSame('User', $entity->name);
    }

    // --- Field attribute class ---

    public function testFieldIsMarkedAsPhpAttribute(): void
    {
        $attrs = $this->reflect('Field')->getAttributes(\Attribute::class);
        $this->assertCount(1, $attrs, 'Field must carry #[\Attribute]');
    }

    public function testFieldConstructorParams(): void
    {
        $params = $this->reflect('Field')->getConstructor()->getParameters();
        $this->assertCount(4, $params);

        $this->assertSame('name', $params[0]->getName());
        $this->assertSame('string', $params[0]->getType()->getName());

        $this->assertSame('type', $params[1]->getName());
        $this->assertSame('string', $params[1]->getType()->getName());

        $this->assertSame('min', $params[2]->getName());
        $this->assertTrue($params[2]->getType()->allowsNull());

        $this->assertSame('max', $params[3]->getName());
        $this->assertTrue($params[3]->getType()->allowsNull());
    }

    public function testFieldCanBeInstantiated(): void
    {
        $fqcn = 'PHireScript\\Sandbox\\src\\output\\Field';
        $field = new $fqcn(name: 'age', type: 'Int', min: 0, max: 120);
        $this->assertSame('age', $field->name);
        $this->assertSame('Int', $field->type);
        $this->assertSame(0, $field->min);
        $this->assertSame(120, $field->max);
    }

    public function testFieldAllowsNullableMinMax(): void
    {
        $fqcn = 'PHireScript\\Sandbox\\src\\output\\Field';
        $field = new $fqcn(name: 'born', type: 'Date', min: null, max: null);
        $this->assertNull($field->min);
        $this->assertNull($field->max);
    }

    // --- User class ---

    public function testUserHasEntityAttribute(): void
    {
        $attrs = $this->reflect('User')->getAttributes();
        $entityAttrs = array_filter($attrs, fn ($a) => str_ends_with($a->getName(), 'Entity'));
        $this->assertCount(1, $entityAttrs, 'User must carry #[Entity(...)]');

        $entityAttr = array_values($entityAttrs)[0];
        $this->assertSame('User', $entityAttr->getArguments()[0]);
    }

    public function testUserNamePropertyHasFieldAttribute(): void
    {
        $prop = $this->reflect('User')->getProperty('name');
        $fieldAttrs = array_filter($prop->getAttributes(), fn ($a) => str_ends_with($a->getName(), 'Field'));
        $this->assertCount(1, $fieldAttrs);

        $args = array_values($fieldAttrs)[0]->getArguments();
        $this->assertSame('name', $args['name']);
        $this->assertSame('String', $args['type']);
        $this->assertSame(3, $args['min']);
        $this->assertSame(255, $args['max']);
    }

    public function testUserLastNamePropertyHasFieldAttribute(): void
    {
        $prop = $this->reflect('User')->getProperty('lastName');
        $fieldAttrs = array_filter($prop->getAttributes(), fn ($a) => str_ends_with($a->getName(), 'Field'));
        $this->assertCount(1, $fieldAttrs);

        $args = array_values($fieldAttrs)[0]->getArguments();
        $this->assertSame('lastName', $args['name']);
        $this->assertSame('String', $args['type']);
    }

    public function testUserBornPropertyHasFieldAttributeWithoutMinMax(): void
    {
        $prop = $this->reflect('User')->getProperty('born');
        $fieldAttrs = array_filter($prop->getAttributes(), fn ($a) => str_ends_with($a->getName(), 'Field'));
        $this->assertCount(1, $fieldAttrs);

        $args = array_values($fieldAttrs)[0]->getArguments();
        $this->assertSame('born', $args['name']);
        $this->assertSame('Date', $args['type']);
        $this->assertArrayNotHasKey('min', $args);
        $this->assertArrayNotHasKey('max', $args);
    }

    public function testUserCanBeInstantiated(): void
    {
        $fqcn = 'PHireScript\\Sandbox\\src\\output\\User';
        $born = new Date('1990-05-20');
        $user = new $fqcn('John', 'Doe', $born);
        $this->assertSame('John', $user->name);
        $this->assertSame('Doe', $user->lastName);
        $this->assertInstanceOf(Date::class, $user->born);
    }

    public function testUserPropertiesHaveCorrectTypes(): void
    {
        $ref = $this->reflect('User');

        $this->assertSame('string', $ref->getProperty('name')->getType()->getName());
        $this->assertSame('string', $ref->getProperty('lastName')->getType()->getName());
        $this->assertSame(Date::class, $ref->getProperty('born')->getType()->getName());
    }
}
