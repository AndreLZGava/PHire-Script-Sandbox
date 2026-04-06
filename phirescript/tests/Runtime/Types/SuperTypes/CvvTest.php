<?php

namespace PHireScript\Tests\Runtime\Types\SuperTypes;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHireScript\Runtime\Types\SuperTypes\Cvv;
use TypeError;

class CvvTest extends TestCase
{
    #[DataProvider('validCvvs')]
    public function testCastValidCvvs(mixed $input, string $expected): void
    {
        $result = Cvv::cast($input);

        $this->assertSame($expected, $result);
    }

    #[DataProvider('invalidCvvs')]
    public function testCastInvalidCvvs(mixed $input): void
    {
        $this->expectException(TypeError::class);

        Cvv::cast($input);
    }

    public static function validCvvs(): array
    {
        return [
        'visa_3_digits_string'        => ['123', '123'],
        'amex_4_digits_string'        => ['1234', '1234'],
        'trimmed_string'              => [' 123 ', '123'],
        'numeric_int_3_digits'        => [123, '123'],
        'numeric_int_4_digits'        => [1234, '1234'],
        'string_with_leading_zero'    => ['012', '012'],
        'string_with_trailing_zero'   => ['120', '120'],
        ];
    }

    public static function invalidCvvs(): array
    {
        return [
        'too_short'           => ['12'],
        'too_long'            => ['12345'],
        'empty_string'        => [''],
        'spaces_only'         => ['   '],
        'alpha_chars'         => ['ABC'],
        'alphanumeric'        => ['12A'],
        'special_chars'       => ['!@#'],
        'float_number'        => [12.3],
        'boolean_true'        => [true],
        'boolean_false'       => [false],
        'array_input'         => [[123]],
        'object_input'        => [(object) []],
        ];
    }
}
