<?php

namespace PHireScript\Tests\Runtime\Types\SuperTypes;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHireScript\Runtime\Types\SuperTypes\Duration;
use TypeError;

class DurationTest extends TestCase
{
    #[DataProvider('invalidDurations')]
    public function testCastInvalidDurations(mixed $input): void
    {
        $this->expectException(TypeError::class);

        Duration::cast($input);
    }

    #[DataProvider('validDurations')]
    public function testCastValidDurations(mixed $input, int $expected): void
    {
        $result = Duration::cast($input);

        $this->assertSame($expected, $result);
    }

    public static function validDurations(): array
    {
        return [
        'zero_int'              => [0, 0],
        'positive_int'          => [120, 120],
        'numeric_string'        => ['300', 300],
        'float_value'           => [12.9, 12],

        'seconds_only'          => ['45s', 45],
        'seconds_with_space'    => ['45 s', 45],

        'minutes_only'          => ['2m', 120],
        'minutes_and_seconds'   => ['2m 30s', 150],

        'hours_only'            => ['1h', 3600],
        'hours_minutes'         => ['1h 15m', 4500],
        'hours_minutes_seconds' => ['1h 2m 3s', 3723],

        'mixed_order'           => ['30s 1h', 3630],

        'repeated_units'        => ['1h 1h 30m', 9000],

        'upper_case_units'      => ['2H 10M', 7800],

        'extra_spaces'          => ['  1h   5m  ', 3900],
        ];
    }

    public static function invalidDurations(): array
    {
        return [
        'negative_int'        => [-1],
        'negative_string'     => ['-5'],
        'empty_string'        => [''],
        'spaces_only'         => ['   '],
        'invalid_unit'        => ['10d'],
        'alpha_string'        => ['abc'],
        'boolean_true'        => [true],
        'boolean_false'       => [false],
        'array_input'         => [[1, 2]],
        'object_input'        => [(object) []],
        ];
    }
}
