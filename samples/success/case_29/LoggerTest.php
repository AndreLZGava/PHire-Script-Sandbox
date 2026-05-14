<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Logger.php';

class LoggerTest extends TestCase
{
    private string $ns = 'PHireScript\Sandbox\src\output';

    private function getReflection(): \ReflectionClass
    {
        $fqcn = $this->ns . '\Logger';

        if (!class_exists($fqcn) && !trait_exists($fqcn)) {
            $this->fail("Logger ({$fqcn}) does not exist");
        }

        return new \ReflectionClass($fqcn);
    }

    public function testNamespaceIsCorrect(): void
    {
        $this->assertEquals($this->ns, $this->getReflection()->getNamespaceName());
    }

    public function testIsTraitNotClass(): void
    {
        $this->assertTrue(
            $this->getReflection()->isTrait(),
            'Logger should compile to a trait, but it is currently compiling to a class (bug)'
        );
    }

    public function testLogMethodExists(): void
    {
        $this->assertTrue($this->getReflection()->hasMethod('log'));
    }

    public function testLogMethodIsPublic(): void
    {
        $this->assertTrue($this->getReflection()->getMethod('log')->isPublic());
    }

    public function testLogMethodReturnTypeIsString(): void
    {
        $returnType = $this->getReflection()->getMethod('log')->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
    }

    public function testLogMethodAcceptsMsgParameter(): void
    {
        $params = $this->getReflection()->getMethod('log')->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals('msg', $params[0]->getName());
    }
}
