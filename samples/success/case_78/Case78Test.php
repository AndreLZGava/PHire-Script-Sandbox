<?php

namespace PHireScript\Sandbox\src\output;

use PHPUnit\Framework\TestCase;
use PHireScript\Runtime\Types\MetaTypes\Date;

require_once __DIR__ . '/Auditable.php';
require_once __DIR__ . '/Other.php';

class Case78Test extends TestCase
{
    private function reflect(): \ReflectionClass
    {
        $fqcn = 'PHireScript\\Sandbox\\src\\output\\Other';
        if (!class_exists($fqcn)) {
            $this->fail("Class {$fqcn} does not exist");
        }
        return new \ReflectionClass($fqcn);
    }

    // --- Auditable attribute class ---

    public function testAuditableIsMarkedAsPhpAttribute(): void
    {
        $fqcn = 'PHireScript\\Sandbox\\src\\output\\Auditable';
        $attrs = (new \ReflectionClass($fqcn))->getAttributes(\Attribute::class);
        $this->assertCount(1, $attrs);
    }

    public function testAuditableConstructorAcceptsReason(): void
    {
        $fqcn = 'PHireScript\\Sandbox\\src\\output\\Auditable';
        $params = (new \ReflectionClass($fqcn))->getConstructor()->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('reason', $params[0]->getName());
        $this->assertSame('string', $params[0]->getType()->getName());
    }

    // --- Class-level built-in attribute ---

    public function testClassHasAllowDynamicProperties(): void
    {
        $attrs = $this->reflect()->getAttributes(\AllowDynamicProperties::class);
        $this->assertCount(1, $attrs);
    }

    // --- Multiple attributes on the same property ---

    public function testOldPasswordHasBothAttributes(): void
    {
        $prop = $this->reflect()->getProperty('oldPassword');
        $attrClasses = array_map(fn ($a) => $a->getName(), $prop->getAttributes());

        $this->assertContains('PHireScript\\Sandbox\\src\\output\\Auditable', $attrClasses);
        $this->assertContains(\Deprecated::class, $attrClasses);
        $this->assertCount(2, $attrClasses);
    }

    public function testOldPasswordAuditableHasCorrectReason(): void
    {
        $prop = $this->reflect()->getProperty('oldPassword');
        $auditableAttrs = array_filter(
            $prop->getAttributes(),
            fn ($a) => str_ends_with($a->getName(), 'Auditable')
        );
        $this->assertCount(1, $auditableAttrs);

        $args = array_values($auditableAttrs)[0]->getArguments();
        $this->assertSame('legacy field', $args['reason']);
    }

    // --- Properties without attributes ---

    public function testNameHasNoAttributes(): void
    {
        $attrs = $this->reflect()->getProperty('name')->getAttributes();
        $this->assertCount(0, $attrs);
    }

    public function testPasswordHasNoAttributes(): void
    {
        $attrs = $this->reflect()->getProperty('password')->getAttributes();
        $this->assertCount(0, $attrs);
    }

    // --- Built-in attribute on method ---

    public function testMethodHasReturnTypeWillChange(): void
    {
        $attrs = $this->reflect()->getMethod('convertOldPasswordToNewOne')->getAttributes(\ReturnTypeWillChange::class);
        $this->assertCount(1, $attrs);
    }

    // --- Instantiation ---

    public function testCanBeInstantiated(): void
    {
        $fqcn = 'PHireScript\\Sandbox\\src\\output\\Other';
        $born = new Date('2000-01-01');
        $obj = new $fqcn('Alice', 'secret', $born);
        $this->assertSame('Alice', $obj->name);
        $this->assertSame('secret', $obj->password);
        $this->assertInstanceOf(Date::class, $obj->oldPassword);
    }
}
