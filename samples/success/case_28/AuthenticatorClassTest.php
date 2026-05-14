<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Another.interface.php';
require_once __DIR__ . '/UserCredentials.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Authenticator.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/AuthenticatorClass.php';

class AuthenticatorClassTest extends TestCase
{
    private string $ns = 'PHireScript\Sandbox\src\output';

    private function getReflection(): \ReflectionClass
    {
        $fqcn = $this->ns . '\AuthenticatorClass';

        if (!class_exists($fqcn)) {
            $this->fail("Class {$fqcn} does not exist");
        }

        return new \ReflectionClass($fqcn);
    }

    private function makeInstance(): object
    {
        $fqcn = $this->ns . '\AuthenticatorClass';
        return new $fqcn();
    }

    public function testNamespaceIsCorrect(): void
    {
        $this->assertEquals($this->ns, $this->getReflection()->getNamespaceName());
    }

    public function testClassExistsAndIsNotInterface(): void
    {
        $reflection = $this->getReflection();
        $this->assertFalse($reflection->isInterface());
        $this->assertFalse($reflection->isTrait());
        $this->assertFalse($reflection->isAbstract());
    }

    public function testImplementsAuthenticator(): void
    {
        $interfaces = $this->getReflection()->getInterfaceNames();
        $this->assertContains($this->ns . '\Authenticator', $interfaces);
    }

    public function testImplementsAnother(): void
    {
        $interfaces = $this->getReflection()->getInterfaceNames();
        $this->assertContains($this->ns . '\Another', $interfaces);
    }

    public function testUsesLoggerTrait(): void
    {
        $traits = $this->getReflection()->getTraitNames();
        $this->assertContains($this->ns . '\Logger', $traits);
    }

    public function testAuthenticateReturnsBool(): void
    {
        $method = $this->getReflection()->getMethod('authenticate');
        $this->assertEquals('bool', $method->getReturnType()->getName());
    }

    public function testAuthenticateAcceptsNullableUserCredentials(): void
    {
        $params = $this->getReflection()->getMethod('authenticate')->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals('credentials', $params[0]->getName());
        $this->assertTrue($params[0]->isOptional());
        $this->assertTrue($params[0]->allowsNull());
    }

    public function testAuthenticateReturnsTrue(): void
    {
        $instance = $this->makeInstance();
        $this->assertTrue($instance->authenticate());
    }

    public function testLogoutReturnsVoid(): void
    {
        $method = $this->getReflection()->getMethod('logout');
        $this->assertEquals('void', $method->getReturnType()->getName());
    }

    public function testReturnNullReturnsNull(): void
    {
        $this->assertNull($this->makeInstance()->returnNull());
    }

    public function testReturnStringSingle(): void
    {
        $this->assertEquals('single quotes', $this->makeInstance()->returnStringSingle());
    }

    public function testReturnStringDouble(): void
    {
        $this->assertEquals('double quotes', $this->makeInstance()->returnStringDouble());
    }

    public function testReturnFloat(): void
    {
        $this->assertEquals(15.2, $this->makeInstance()->returnFloat());
    }

    public function testReturnInt(): void
    {
        $this->assertEquals(10, $this->makeInstance()->returnInt());
    }

    public function testReturnArrayEmpty(): void
    {
        $this->assertEquals([], $this->makeInstance()->returnArrayEmpty());
    }

    public function testReturnArrayComplete(): void
    {
        $expected = ['example' => ['another', 'array']];
        $this->assertEquals($expected, $this->makeInstance()->returnArrayComplete());
    }

    public function testReturnObjectEmpty(): void
    {
        $result = $this->makeInstance()->returnObjectEmpty();
        $this->assertIsObject($result);
        $this->assertEmpty((array) $result);
    }

    public function testReturnObject(): void
    {
        $result = $this->makeInstance()->returnObject();
        $this->assertIsObject($result);
        $this->assertEquals(1, $result->test);
    }
}
