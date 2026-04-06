<?php

namespace PHireScript\Tests\Runtime\Types\SuperTypes;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHireScript\Runtime\Types\SuperTypes\ExpiryDate;
use TypeError;

class ExpiryDateTest extends TestCase
{
    #[DataProvider('validExpiryDates')]
    public function testCastValidExpiryDates(mixed $input, string $expected): void
    {
        $result = ExpiryDate::cast($input);

        $this->assertSame($expected, $result);
    }

    #[DataProvider('invalidExpiryDates')]
    public function testCastInvalidExpiryDates(mixed $input): void
    {
        $this->expectException(TypeError::class);

        ExpiryDate::cast($input);
    }

    #[DataProvider('pastExpiryDates')]
    public function testIsPast(string $value): void
    {
        $this->assertTrue(ExpiryDate::isPast($value));
    }

    #[DataProvider('futureExpiryDates')]
    public function testIsNotPast(string $value): void
    {
        $this->assertFalse(ExpiryDate::isPast($value));
    }

    #[DataProvider('formatCases')]
    public function testFormat(string $value, string $style, string $expected): void
    {
        $this->assertSame($expected, ExpiryDate::format($value, $style));
    }

    public static function validExpiryDates(): array
    {
        $currentYear  = (int)date('y');
        $currentMonth = (int)date('n');

        $nextMonth = str_pad((string)(($currentMonth % 12) + 1), 2, '0', STR_PAD_LEFT);
        $nextYear  = str_pad((string)($currentMonth === 12 ? $currentYear + 1 : $currentYear), 2, '0', STR_PAD_LEFT);

        return [
        'mm_yy_slash'        => ['12/29', '1229'],
        'mm_yy_dash'         => ['12-29', '1229'],
        'mm_yyyy'            => ['12/2029', '1229'],
        'numbers_only'       => ['1229', '1229'],
        'spaces_and_symbols' => [' 12 / 29 ', '1229'],
        'future_month_same_year' => [
        $nextMonth . '/' . str_pad((string)$currentYear, 2, '0', STR_PAD_LEFT),
        $nextMonth . str_pad((string)$currentYear, 2, '0', STR_PAD_LEFT),
        ],
        ];
    }

    public static function invalidExpiryDates(): array
    {
        $currentMonth = (int)date('n');
        $currentYear  = (int)date('y');

        if ($currentMonth === 1) {
            $pastMonth = 12;
            $pastYear  = $currentYear - 1;
        } else {
            $pastMonth = $currentMonth - 1;
            $pastYear  = $currentYear;
        }

        return [
        'empty_string'      => [''],
        'spaces_only'       => ['   '],
        'non_string_int'    => [1234],
        'boolean_true'      => [true],
        'array_input'       => [['12/29']],
        'object_input'      => [(object) []],
        'invalid_month_00'  => ['00/29'],
        'invalid_month_13'  => ['13/29'],
        'past_year'         => ["12/$pastYear"],
        'past_month_same_year' => [
        str_pad((string)$pastMonth, 2, '0', STR_PAD_LEFT)
          . '/'
          . str_pad((string)$pastYear, 2, '0', STR_PAD_LEFT)
        ],
        'too_short'         => ['129'],
        'too_long'          => ['12299'],
        'letters'           => ['ab/cd'],
        ];
    }

    public static function pastExpiryDates(): array
    {
        $pastYear = str_pad((string)((int)date('y') - 1), 2, '0', STR_PAD_LEFT);

        return [
        'past_year' => ["12$pastYear"],
        'past_month_same_year' => [
        str_pad((string)((int)date('n') - 1 ?: 12), 2, '0', STR_PAD_LEFT)
          . '/'
          . str_pad((string)date('y'), 2, '0', STR_PAD_LEFT)
        ],
        ];
    }

    public static function futureExpiryDates(): array
    {
        $currentYear  = str_pad((string)date('y'), 2, '0', STR_PAD_LEFT);
        $nextYear     = str_pad((string)((int)date('y') + 1), 2, '0', STR_PAD_LEFT);

        return [
        'future_year' => ["01$nextYear"],
        'future_month_same_year' => [
        str_pad((string)((int)date('n') + 1), 2, '0', STR_PAD_LEFT) . $currentYear
        ],
        ];
    }

    public static function formatCases(): array
    {
        return [
        'short' => ['1229', 'short', '12/29'],
        'full'  => ['1229', 'full', '12/2029'],
        'long'  => ['1229', 'long', '12 de 2029'],
        'iso'   => ['1229', 'iso', '2029-12-01'],
        'default' => ['1229', 'unknown', '12/29'],
        ];
    }
}
