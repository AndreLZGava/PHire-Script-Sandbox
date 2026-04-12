<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Another.interface.php';

class AnotherInterfaceTest extends TestCase
{
    private string $expectedNamespace = 'PHireScript\Sandbox\src\output';
    private string $interfaceName = 'Another';

    private function getReflection(): \ReflectionClass
    {
        $fullClassName = $this->expectedNamespace . '\\' . $this->interfaceName;

        if (!interface_exists($fullClassName)) {
            $this->fail("Interface {$fullClassName} does not exist");
        }

        return new \ReflectionClass($fullClassName);
    }

    public function testNamespaceIsCorrect()
    {
        $reflection = $this->getReflection();

        $this->assertEquals(
            $this->expectedNamespace,
            $reflection->getNamespaceName()
        );
    }

    public function testIsInterface()
    {
        $reflection = $this->getReflection();

        $this->assertTrue($reflection->isInterface());
    }

    public function testMethodsExistAndArePublic()
    {
        $reflection = $this->getReflection();

        $expectedMethods = [
            'save',
            'delete',
            'getCompleteUserName'
        ];

        foreach ($expectedMethods as $methodName) {
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

    public function testMethodReturnTypes()
    {
        $reflection = $this->getReflection();

        $methods = [
            'save' => 'bool',
            'delete' => 'void',
            'getCompleteUserName' => '?string',
        ];

        foreach ($methods as $methodName => $expectedType) {
            $method = $reflection->getMethod($methodName);

            $returnType = $method->getReturnType();

            $this->assertNotNull(
                $returnType,
                "Method {$methodName} has no return type"
            );

            $typeName = $returnType instanceof \ReflectionNamedType
                ? $returnType->getName()
                : (string) $returnType;

            if ($returnType->allowsNull() && $typeName !== 'mixed') {
                $typeName = '?' . $typeName;
            }

            $this->assertEquals(
                $expectedType,
                $typeName,
                "Return type mismatch on {$methodName}"
            );
        }
    }

    public function testMethodParameters()
    {
        $reflection = $this->getReflection();

        $method = $reflection->getMethod('save');
        $params = $method->getParameters();

        $this->assertCount(1, $params);

        $param = $params[0];

        $this->assertEquals('data', $param->getName());

        $type = $param->getType();
        $this->assertNotNull($type);

        $this->assertEquals('array', $type->getName());
    }
}
