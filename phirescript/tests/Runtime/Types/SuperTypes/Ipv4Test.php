<?php

namespace PHireScript\Tests\Runtime\Types\SuperTypes;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHireScript\Runtime\Types\SuperTypes\Ipv4;
use TypeError;

class Ipv4Test extends TestCase
{
    #[DataProvider('validIps')]
    public function testCastValidIps(mixed $input): void
    {
        $result = Ipv4::cast($input);

        $this->assertSame($input, $result);
    }

    #[DataProvider('invalidIps')]
    public function testCastInvalidIps(mixed $input): void
    {
        $this->expectException(TypeError::class);

        Ipv4::cast($input);
    }

    public static function validIps(): array
    {
        return [
        'localhost'          => ['127.0.0.1'],
        'zero_ip'            => ['0.0.0.0'],
        'broadcast'          => ['255.255.255.255'],
        'private_class_a'    => ['10.0.0.1'],
        'private_class_b'    => ['172.16.0.1'],
        'private_class_c'    => ['192.168.1.1'],
        'public_google'      => ['8.8.8.8'],
        'edge_values_low'    => ['1.0.0.0'],
        'edge_values_high'   => ['223.255.255.255'],
        ];
    }

    public static function invalidIps(): array
    {
        return [
        'ipv6'               => ['2001:db8::1'],
        'too_many_blocks'    => ['192.168.0.1.1'],
        'too_few_blocks'     => ['192.168.1'],
        'negative_octet'     => ['-1.0.0.0'],
        'octet_overflow'     => ['256.0.0.1'],
        'alpha_chars'        => ['abc.def.ghi.jkl'],
        'mixed_chars'        => ['192.168.one.1'],
        'empty_string'       => [''],
        'spaces'             => ['   '],
        'null'               => [null],
        'int_input'          => [123456],
        'boolean_true'       => [true],
        'array_input'        => [['127.0.0.1']],
        'object_input'       => [(object) []],
        ];
    }
}
