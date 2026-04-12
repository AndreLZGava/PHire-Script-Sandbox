<?php

use PHireScript\Sandbox\src\output\UserInterface;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/UserInterface.php';

class UserInterfaceTest extends TestCase
{
    private function getReflection(): ReflectionClass
    {
        $interface = UserInterface::class;

        if (!interface_exists($interface)) {
            $this->fail("Interface {$interface} does not exist");
        }

        return new ReflectionClass($interface);
    }

    public function testIsInterface()
    {
        $reflection = $this->getReflection();

        $this->assertTrue($reflection->isInterface());
    }

    public function testMethodsExist()
    {
        $reflection = $this->getReflection();

        $methods = [
            'save',
            'delete',
            'getCompleteUserName',
        ];

        foreach ($methods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "Method {$method} does not exist"
            );
        }
    }

    public function testSaveMethodSignature()
    {
        $method = $this->getReflection()->getMethod('save');

        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertEquals('data', $params[0]->getName());
        $this->assertEquals('array', $params[0]->getType()->getName());

        $this->assertEquals(
            'bool',
            $method->getReturnType()->getName()
        );
    }

    public function testDeleteMethodSignature()
    {
        $method = $this->getReflection()->getMethod('delete');

        $this->assertCount(0, $method->getParameters());

        $this->assertEquals(
            'void',
            $method->getReturnType()->getName()
        );
    }

    public function testGetCompleteUserNameReturnType()
    {
        $method = $this->getReflection()->getMethod('getCompleteUserName');

        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);

        if ($returnType instanceof ReflectionUnionType) {
            $types = array_map(
                fn($t) => $t->getName(),
                $returnType->getTypes()
            );

            $this->assertContains('string', $types);
            $this->assertContains('null', $types);
        } else {
            $this->assertEquals('string', $returnType->getName());
            $this->assertTrue($returnType->allowsNull());
        }
    }
}
