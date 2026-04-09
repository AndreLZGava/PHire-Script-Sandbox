<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Compiled/Classes/Another.interface.php';
require_once __DIR__ . '/../../Compiled/Classes/Authenticator.php';
require_once __DIR__ . '/../../Compiled/Classes/AuthenticatorClass.php';
require_once __DIR__ . '/../../Compiled/Classes/UserCredentials.php';
require_once __DIR__ . '/../../Compiled/Classes/Logger.php';

use PHireScript\Classes\AuthenticatorClass;
use PHireScript\Classes\UserCredentials;
use PHireScript\Classes\Logger;
use PHireScript\Classes\Authenticator;
use PHireScript\Classes\Another;
use PHireScript\Runtime\Types\MetaTypes\Date;

class AuthenticatorClassTest extends TestCase
{
    public function testAuthenticateReturnsBool()
    {
        $auth = new AuthenticatorClass();

        $credentials = new UserCredentials(
            'Andre',
            'andrelzgava@gmail.com',
            new Date('1993-10-15'),
            '127.0.0.1'
        );

        $this->assertIsBool(
            $auth->authenticate($credentials)
        );
    }

    public function testReturnTypes()
    {
        $auth = new AuthenticatorClass();

        $this->assertNull($auth->returnNull());

        $this->assertIsString($auth->returnStringSingle());
        $this->assertIsString($auth->returnStringDouble());

        $this->assertIsFloat($auth->returnFloat());
        $this->assertIsInt($auth->returnInt());

        $this->assertIsArray($auth->returnArrayEmpty());
        $this->assertIsArray($auth->returnArrayComplete());

        $this->assertIsObject($auth->returnObjectEmpty());
        $this->assertIsObject($auth->returnObject());
    }

    public function testReturnArrayStructure()
    {
        $auth = new AuthenticatorClass();

        $result = $auth->returnArrayComplete();

        $this->assertArrayHasKey('example', $result);
        $this->assertIsArray($result['example']);
    }

    public function testReturnObjectStructure()
    {
        $auth = new AuthenticatorClass();

        $result = $auth->returnObject();

        $this->assertIsObject($result);
        $this->assertEquals(1, $result->test);
    }
}
