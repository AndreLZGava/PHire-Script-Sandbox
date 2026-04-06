<?php

namespace PHireScript\Tests\Runtime\Types\SuperTypes;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHireScript\Runtime\Types\SuperTypes\Json;
use TypeError;

class JsonTest extends TestCase
{
    #[DataProvider('validJsonValues')]
    public function testCastValidJson(mixed $input, mixed $expected): void
    {
        $result = Json::cast($input);

        $this->assertEquals($expected, $result);
    }

    #[DataProvider('invalidJsonValues')]
    public function testCastInvalidJson(mixed $input): void
    {
        $this->expectException(TypeError::class);

        Json::cast($input);
    }

    public static function validJsonValues(): array
    {
        return [
            'json_object_string' => [
                '{"name":"John","age":30}',
                ['name' => 'John', 'age' => 30],
            ],
            'json_array_string' => [
                '[1, 2, 3]',
                [1, 2, 3],
            ],
            'nested_json' => [
                '{"user":{"id":1,"roles":["admin","user"]}}',
                ['user' => ['id' => 1, 'roles' => ['admin', 'user']]],
            ],
            'empty_object' => [
                '{}',
                [],
            ],
            'empty_array' => [
                '[]',
                [],
            ],
            'array_input' => [
                ['a' => 1, 'b' => 2],
                ['a' => 1, 'b' => 2],
            ],
            'object_input' => [
                (object) ['a' => 1],
                (object) ['a' => 1],
            ],
        ];
    }

    public static function invalidJsonValues(): array
    {
        return [
            'invalid_json_string' => ['{invalid json}'],
            'plain_string'        => ['hello world'],
            'numeric_string'      => ['123'],
            'integer'             => [123],
            'float'               => [12.3],
            'boolean_true'        => [true],
            'boolean_false'       => [false],
            'null_value'          => [null],
        ];
    }
}
