<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Another.interface.php';
require_once __DIR__ . '/UserCredentials.php';
require_once __DIR__ . '/Authenticator.php';

class AuthenticatorTest extends TestCase
{
    private string $ns = 'PHireScript\Sandbox\src\output';

    private function getReflection(): \ReflectionClass
    {
        $fqcn = $this->ns . '\\Authenticator';

        if (!interface_exists($fqcn)) {
            $this->fail("Interface {$fqcn} does not exist");
        }

        return new \ReflectionClass($fqcn);
    }

    public function testNamespaceIsCorrect(): void
    {
        $this->assertEquals($this->ns, $this->getReflection()->getNamespaceName());
    }

    public function testIsInterface(): void
    {
        $this->assertTrue($this->getReflection()->isInterface());
    }

    public function testExtendsAnother(): void
    {
        $reflection = $this->getReflection();
        $parents = $reflection->getInterfaceNames();
        $this->assertContains($this->ns . '\\Another', $parents);
    }

    public function testAuthenticateMethodExists(): void
    {
        $this->assertTrue($this->getReflection()->hasMethod('authenticate'));
    }

    public function testAuthenticateMethodIsPublic(): void
    {
        $this->assertTrue($this->getReflection()->getMethod('authenticate')->isPublic());
    }

    public function testAuthenticateRetursBool(): void
    {
        $returnType = $this->getReflection()->getMethod('authenticate')->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('bool', $returnType->getName());
    }

    public function testAuthenticateAcceptsUserCredentials(): void
    {
        $params = $this->getReflection()->getMethod('authenticate')->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals('credentials', $params[0]->getName());
        $this->assertEquals($this->ns . '\\UserCredentials', $params[0]->getType()->getName());
    }

    public function testLogoutMethodExists(): void
    {
        $this->assertTrue($this->getReflection()->hasMethod('logout'));
    }

    public function testLogoutReturnsVoid(): void
    {
        $returnType = $this->getReflection()->getMethod('logout')->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('void', $returnType->getName());
    }

    public function testInheritedMethodsSaveDeleteGetCompleteUserName(): void
    {
        $reflection = $this->getReflection();
        foreach (['save', 'delete', 'getCompleteUserName'] as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Inherited method {$method} missing");
        }
    }
}
