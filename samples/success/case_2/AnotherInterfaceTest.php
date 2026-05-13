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

    public function testNamespaceIsCorrect(): void
    {
        $this->assertEquals($this->expectedNamespace, $this->getReflection()->getNamespaceName());
    }

    public function testIsInterface(): void
    {
        $this->assertTrue($this->getReflection()->isInterface());
    }

    public function testMethodsExistAndArePublic(): void
    {
        $reflection = $this->getReflection();

        foreach (['save', 'delete', 'getCompleteUserName'] as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Method {$method} does not exist");
            $this->assertTrue($reflection->getMethod($method)->isPublic(), "Method {$method} is not public");
        }
    }

    public function testMethodReturnTypes(): void
    {
        $reflection = $this->getReflection();

        $expected = ['save' => 'bool', 'delete' => 'void', 'getCompleteUserName' => '?string'];

        foreach ($expected as $methodName => $expectedType) {
            $returnType = $reflection->getMethod($methodName)->getReturnType();
            $this->assertNotNull($returnType, "No return type for {$methodName}");

            $typeName = $returnType instanceof \ReflectionNamedType
                ? $returnType->getName()
                : (string) $returnType;

            if ($returnType->allowsNull() && $typeName !== 'mixed') {
                $typeName = '?' . $typeName;
            }

            $this->assertEquals($expectedType, $typeName, "Return type mismatch on {$methodName}");
        }
    }

    public function testSaveMethodParameter(): void
    {
        $params = $this->getReflection()->getMethod('save')->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals('data', $params[0]->getName());
        $this->assertEquals('array', $params[0]->getType()->getName());
    }
}
