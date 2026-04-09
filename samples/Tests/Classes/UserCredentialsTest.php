<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Compiled/Classes/UserCredentials.php';

use PHireScript\Classes\UserCredentials;
use PHireScript\Runtime\Types\MetaTypes\Date;

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


    public function testUnionTypeIpValidationException()
    {
        $credentials = new UserCredentials(
            'Andre',
            'andrelzgava@gmail.com',
            new Date('1993-10-15'),
            '127.0.0.1'
        );

        $this->expectException(Error::class);

        $this->assertIsString($credentials->lastIp);
    }
}
