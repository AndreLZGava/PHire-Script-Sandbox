<?php

use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\Types\SuperTypes\Email;
use PHireScript\Runtime\Types\SuperTypes\Ipv4;
use PHireScript\Runtime\Types\SuperTypes\Ipv6;
use PHireScript\Runtime\Types\SuperTypes\Uuid;
use PHireScript\Runtime\Types\SuperTypes\Color;
use PHireScript\Runtime\Types\SuperTypes\Url;
use PHireScript\Runtime\Types\SuperTypes\Cron;
use PHireScript\Runtime\Types\SuperTypes\Duration;
use PHireScript\Runtime\Types\SuperTypes\Json;
use PHireScript\Runtime\Types\SuperTypes\Mac;
use PHireScript\Runtime\Types\SuperTypes\Slug;
use PHPUnit\Framework\TestCase;

class SuperTypesTest extends TestCase
{
    public function testValidCasts()
    {
        $this->assertEquals('andrelzgava@gmail.com', Email::cast('andrelzgava@gmail.com'));

        $this->assertEquals('127.0.0.1', Ipv4::cast('127.0.0.1'));

        $this->assertEquals(
            '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            Ipv6::cast('2001:0db8:85a3:0000:0000:8a2e:0370:7334')
        );

        $this->assertNotEmpty(Uuid::cast());

        $result = Uuid::cast('550E8400-E29B-41D4-A716-446655440000');

        $this->assertEquals(
            strtolower('550E8400-E29B-41D4-A716-446655440000'),
            $result
        );

        $this->assertEquals('#FFFFFF', Color::cast('#fff'));

        $this->assertEquals('https://www.google.com', Url::cast('https://www.google.com'));

        $this->assertEquals('@DAILY', Cron::cast('@daily'));

        $this->assertEquals(300, Duration::cast('5m'));

        $this->assertEquals(
            json_decode('{"name":"John","age":30}', true),
            Json::cast('{"name":"John","age":30}')
        );

        $this->assertEquals('00:1a:2b:3c:4d:5e', Mac::cast('00:1a:2b:3c:4d:5e'));

        $this->assertEquals('hello-world', Slug::cast('hello World'));
    }

    public function testInvalidEmailThrows()
    {
        $this->expectException(\Throwable::class);
        Email::cast('invalid-email');
    }

    public function testInvalidIpv4Throws()
    {
        $this->expectException(\Throwable::class);
        Ipv4::cast('999.999.999.999');
    }

    public function testInvalidIpv6Throws()
    {
        $this->expectException(\Throwable::class);
        Ipv6::cast('invalid-ipv6');
    }

    public function testInvalidUuidThrows()
    {
        $this->expectException(\Throwable::class);
        Uuid::cast('invalid-uuid');
    }
}
