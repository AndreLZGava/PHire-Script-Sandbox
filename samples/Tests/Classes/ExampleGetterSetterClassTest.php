<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Compiled/Classes/ExampleGetterSetterClass.php';

use PHireScript\Classes\ExampleGetterSetterClass;

class ExampleGetterSetterClassTest extends TestCase
{
    public function testPublicGettersAndSetters()
    {
        $obj = new ExampleGetterSetterClass();

        $obj->id = 10;
        $obj->setEmail('test@email.com');
        $obj->setUsername('Andre');

        $this->assertEquals(10, $obj->getId());
        $this->assertEquals('Andre', $obj->getUsername());
        $this->assertEquals('test@email.com', $obj->email);
    }

    public function testPrivateGetterIsNotAccessible()
    {
        $obj = new ExampleGetterSetterClass();

        $this->expectException(Error::class);

        $obj->getIsAdmin();
    }

    public function testProtectedSetterIsNotAccessible()
    {
        $obj = new ExampleGetterSetterClass();

        $this->expectException(Error::class);

        $obj->setIsAdmin(true);
    }

    public function testProtectedGetterIsNotAccessible()
    {
        $obj = new ExampleGetterSetterClass();

        $this->expectException(Error::class);

        $obj->getMetadata();
    }

    public function testPrivateSetterIsNotAccessible()
    {
        $obj = new ExampleGetterSetterClass();

        $this->expectException(Error::class);

        $obj->setMetadata([]);
    }
}
