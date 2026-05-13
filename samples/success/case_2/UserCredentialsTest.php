<?php

use PHireScript\Sandbox\src\output\UserCredentials;
use PHireScript\Runtime\Types\MetaTypes\Date;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/UserCredentials.php';

class UserCredentialsTest extends TestCase
{
    private function makeCredentials(string $ip = '127.0.0.1'): UserCredentials
    {
        return new UserCredentials('Andre', 'andrelzgava@gmail.com', new Date('1993-10-15'), $ip);
    }

    public function testCanInstantiate(): void
    {
        $c = $this->makeCredentials();
        $this->assertEquals('Andre', $c->login);
        $this->assertEquals('andrelzgava@gmail.com', $c->userEmail);
    }

    public function testLoginIsPublicString(): void
    {
        $reflection = new \ReflectionClass($this->makeCredentials());
        $prop = $reflection->getProperty('login');
        $this->assertTrue($prop->isPublic());
        $this->assertEquals('string', $prop->getType()->getName());
    }

    public function testUserEmailIsPublicString(): void
    {
        $reflection = new \ReflectionClass($this->makeCredentials());
        $prop = $reflection->getProperty('userEmail');
        $this->assertTrue($prop->isPublic());
    }

    public function testDateBirthIsProtected(): void
    {
        $this->expectException(Error::class);
        $this->makeCredentials()->dateBirth;
    }

    public function testLastIpIsPrivate(): void
    {
        $this->expectException(Error::class);
        $this->makeCredentials()->lastIp;
    }

    public function testDateBirthIsDateInstance(): void
    {
        $reflection = new \ReflectionClass($this->makeCredentials());
        $prop = $reflection->getProperty('dateBirth');
        $prop->setAccessible(true);
        $this->assertInstanceOf(Date::class, $prop->getValue($this->makeCredentials()));
    }

    public function testInvalidEmailThrowsException(): void
    {
        $this->expectException(\Throwable::class);
        new UserCredentials('Andre', 'not-an-email', new Date('1993-10-15'), '127.0.0.1');
    }

    public function testInvalidIpThrowsException(): void
    {
        $this->expectException(\Throwable::class);
        $this->makeCredentials('invalid-ip');
    }

    public function testAcceptsIpv4(): void
    {
        $c = $this->makeCredentials('192.168.1.1');
        $this->assertInstanceOf(UserCredentials::class, $c);
    }

    public function testAcceptsIpv6(): void
    {
        $c = $this->makeCredentials('2001:db8::1');
        $this->assertInstanceOf(UserCredentials::class, $c);
    }
}
