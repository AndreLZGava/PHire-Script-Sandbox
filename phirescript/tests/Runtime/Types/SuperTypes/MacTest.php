<?php

namespace PHireScript\Tests\Runtime\Types\SuperTypes;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHireScript\Runtime\Types\SuperTypes\Mac;
use TypeError;

class MacTest extends TestCase
{
    #[DataProvider('validMacs')]
    public function testCastValidMacs(mixed $input): void
    {
        $result = Mac::cast($input);

        $this->assertSame($input, $result);
    }

    #[DataProvider('invalidMacs')]
    public function testCastInvalidMacs(mixed $input): void
    {
        $this->expectException(TypeError::class);

        Mac::cast($input);
    }

    public static function validMacs(): array
    {
        return [
        'colon_lowercase'     => ['00:1a:2b:3c:4d:5e'],
        'colon_uppercase'     => ['AA:BB:CC:DD:EE:FF'],
        'dash_lowercase'      => ['00-1a-2b-3c-4d-5e'],
        'dash_uppercase'      => ['AA-BB-CC-DD-EE-FF'],
        'mixed_case'          => ['Aa:Bb:Cc:Dd:Ee:Ff'],
        'zero_mac'            => ['00:00:00:00:00:00'],
        'broadcast_mac'       => ['FF:FF:FF:FF:FF:FF'],
        'dot_notation'        => ['001a.2b3c.4d5e'],
        ];
    }

    public static function invalidMacs(): array
    {
        return [
        'too_short'           => ['00:1a:2b:3c:4d'],
        'too_long'            => ['00:1a:2b:3c:4d:5e:6f'],
        'invalid_hex'         => ['00:1g:2b:3c:4d:5e'],
        'missing_separators'  => ['001a2b3c4d5e'],
        'mixed_separators'    => ['00:1a-2b:3c-4d:5e'],
        'spaces'              => ['   '],
        'empty_string'        => [''],
        'null'                => [null],
        'int_input'           => [123456],
        'boolean_true'        => [true],
        'array_input'         => [['00:1a:2b:3c:4d:5e']],
        'object_input'        => [(object) []],
        ];
    }
}
