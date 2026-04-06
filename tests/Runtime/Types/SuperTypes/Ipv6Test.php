<?php

namespace PHireScript\Tests\Runtime\Types\SuperTypes;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHireScript\Runtime\Types\SuperTypes\Ipv6;
use TypeError;

class Ipv6Test extends TestCase
{
    #[DataProvider('validIps')]
    public function testCastValidIps(mixed $input): void
    {
        $result = Ipv6::cast($input);

        $this->assertSame($input, $result);
    }

    #[DataProvider('invalidIps')]
    public function testCastInvalidIps(mixed $input): void
    {
        $this->expectException(TypeError::class);

        Ipv6::cast($input);
    }

    public static function validIps(): array
    {
        return [
        'loopback'                 => ['::1'],
        'unspecified'              => ['::'],
        'full_notation'            => ['2001:0db8:85a3:0000:0000:8a2e:0370:7334'],
        'compressed'               => ['2001:db8:85a3::8a2e:370:7334'],
        'link_local'               => ['fe80::1'],
        'multicast'                => ['ff02::1'],
        'ipv4_mapped'              => ['::ffff:192.168.1.1'],
        'documentation_prefix'     => ['2001:db8::1'],
        'leading_zeros'            => ['2001:0db8::0001'],
        ];
    }

    public static function invalidIps(): array
    {
        return [
        'ipv4'                     => ['127.0.0.1'],
        'too_many_groups'          => ['2001:db8:1:2:3:4:5:6:7'],
        'double_compression'       => ['2001::85a3::8a2e'],
        'invalid_hex'              => ['2001:db8:85a3::zzzz'],
        'trailing_colon'           => ['2001:db8:85a3::1:'],
        'leading_colon'            => [':2001:db8::1'],
        'empty_string'             => [''],
        'spaces'                   => ['   '],
        'null'                     => [null],
        'int_input'                => [123456],
        'boolean_true'             => [true],
        'array_input'              => [['::1']],
        'object_input'             => [(object) []],
        ];
    }
}
