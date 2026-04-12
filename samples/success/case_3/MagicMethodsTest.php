<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/MagicMethods.php';

class MagicMethodsTest extends TestCase
{
    private function getReflection(): \ReflectionClass
    {
        $class = 'PHireScript\\Sandbox\\src\\output\\MagicMethods';

        if (!class_exists($class)) {
            $this->fail("Class {$class} does not exist");
        }

        return new \ReflectionClass($class);
    }

    public function testNamespace()
    {
        $reflection = $this->getReflection();

        $this->assertEquals(
            'PHireScript\\Sandbox\\src\\output',
            $reflection->getNamespaceName()
        );
    }

    public function testMagicMethodsExistAndArePublic()
    {
        $reflection = $this->getReflection();

        $methods = [
            '__construct',
            '__destruct',
            '__get',
            '__set',
            '__unset',
            '__call',
            '__callStatic',
            '__toString',
            '__serialize',
            '__sleep',
            '__wakeup',
            '__clone',
            '__debugInfo',
        ];

        foreach ($methods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "Method {$methodName} does not exist"
            );

            $method = $reflection->getMethod($methodName);

            $this->assertTrue(
                $method->isPublic(),
                "Method {$methodName} is not public"
            );
        }
    }

    public function testCallStaticIsStatic()
    {
        $reflection = $this->getReflection();

        $method = $reflection->getMethod('__callStatic');

        $this->assertTrue($method->isStatic());
    }

    public function testReturnTypes()
    {
        $reflection = $this->getReflection();

        $expected = [
            '__get' => 'mixed',
            '__set' => 'void',
            '__call' => 'mixed',
            '__callStatic' => 'mixed',
            '__toString' => 'string',
            '__serialize' => 'array',
            '__sleep' => 'array',
            '__wakeup' => 'void',
            '__clone' => 'void',
            '__debugInfo' => 'array',
        ];

        foreach ($expected as $methodName => $type) {
            $method = $reflection->getMethod($methodName);

            $returnType = $method->getReturnType();

            $this->assertNotNull($returnType, "No return type for {$methodName}");

            $this->assertEquals(
                $type,
                $returnType->getName(),
                "Return type mismatch for {$methodName}"
            );
        }
    }

    public function testConstructParameters()
    {
        $reflection = $this->getReflection();

        $method = $reflection->getMethod('__construct');
        $params = $method->getParameters();

        $this->assertCount(2, $params);

        $this->assertEquals('string', $params[0]->getType()->getName());
        $this->assertEquals('test', $params[0]->getName());

        $this->assertEquals('bool', $params[1]->getType()->getName());
        $this->assertEquals('isSelf', $params[1]->getName());
    }

    public function testHasHasIsNotMagic()
    {
        $reflection = $this->getReflection();

        $this->assertTrue($reflection->hasMethod('hasHas'));

        $method = $reflection->getMethod('hasHas');

        $this->assertFalse(str_starts_with($method->getName(), '__'));
    }
}
