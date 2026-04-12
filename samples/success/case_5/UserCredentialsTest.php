<?php

use PHireScript\Sandbox\src\output\UserCredentials;
use PHireScript\Runtime\Types\MetaTypes\Date;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/UserCredentials.php';

class UserCredentialsTest extends TestCase
{
    public function testCanInstantiateUserCredentials()
    {
        $date = new Date('1993-10-15');

        $credentials = new UserCredentials(
            'Andre',
            'andrelzgava@gmail.com',
            $date,
            '127.0.0.1'
        );

        $this->assertEquals('Andre', $credentials->login);
        $this->assertEquals('andrelzgava@gmail.com', $credentials->userEmail);
    }

    public function testDateBirthIsProtected()
    {
        $credentials = new UserCredentials(
            'Andre',
            'andrelzgava@gmail.com',
            new Date('1993-10-15'),
            '127.0.0.1'
        );

        $this->expectException(Error::class);

        $credentials->dateBirth;
    }

    public function testLastIpIsPrivate()
    {
        $credentials = new UserCredentials(
            'Andre',
            'andrelzgava@gmail.com',
            new Date('1993-10-15'),
            '127.0.0.1'
        );

        $this->expectException(Error::class);

        $credentials->lastIp;
    }

    public function testInvalidIpThrowsException()
    {
        $this->expectException(\Throwable::class);

        new UserCredentials(
            'Andre',
            'andrelzgava@gmail.com',
            new Date('1993-10-15'),
            'invalid-ip'
        );
    }

    public function testDateIsConvertedWhenStringIsPassed()
    {
        $credentials = new UserCredentials(
            'Andre',
            'andrelzgava@gmail.com',
            new Date('1993-10-15'),
            '127.0.0.1'
        );

        $reflection = new ReflectionClass($credentials);
        $property = $reflection->getProperty('dateBirth');
        $property->setAccessible(true);

        $value = $property->getValue($credentials);

        $this->assertInstanceOf(Date::class, $value);
    }
}
