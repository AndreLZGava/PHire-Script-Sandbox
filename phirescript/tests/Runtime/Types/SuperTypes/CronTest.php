<?php

namespace PHireScript\Tests\Runtime\Types\SuperTypes;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHireScript\Runtime\Types\SuperTypes\Cron;
use TypeError;

class CronTest extends TestCase
{
    #[DataProvider('validCrons')]
    public function testCastValidCrons(string $input, string $expected): void
    {
        $result = Cron::cast($input);
        $this->assertSame($expected, $result);
    }

    #[DataProvider('invalidCrons')]
    public function testCastInvalidCrons(mixed $input): void
    {
        $this->expectException(TypeError::class);
        Cron::cast($input);
    }

    public static function validCrons(): array
    {
        return [

            'every_minute' => [
                '* * * * *',
                '* * * * *',
            ],

            'specific_time' => [
                '30 14 1 1 1',
                '30 14 1 1 1',
            ],

            'with_ranges' => [
                '0 9-17 * * 1-5',
                '0 9-17 * * 1-5',
            ],

            'with_lists' => [
                '0 9,12,18 * * *',
                '0 9,12,18 * * *',
            ],

            'with_steps' => [
                '*/5 * * * *',
                '*/5 * * * *',
            ],

            'with_seconds' => [
                '0 */5 * * * *',
                '0 */5 * * * *',
            ],

            'month_names' => [
                '0 0 1 jan *',
                '0 0 1 JAN *',
            ],

            'day_names' => [
                '0 0 * * mon-fri',
                '0 0 * * MON-FRI',
            ],

            'sunday_zero' => [
                '0 0 * * 0',
                '0 0 * * 0',
            ],

            'sunday_seven' => [
                '0 0 * * 7',
                '0 0 * * 7',
            ],

            'extra_spaces' => [
                '  0   0   *   *   *  ',
                '0 0 * * *',
            ],

            'macro_daily' => [
                '@daily',
                '@DAILY',
            ],

            'macro_weekly' => [
                '@weekly',
                '@WEEKLY',
            ],
        ];
    }

    public static function invalidCrons(): array
    {
        return [

            'array_input' => [[ '* * * * *' ]],
            'bool_input'  => [true],
            'int_input'   => [12345],
            'object'      => [(object) []],

            'too_few_fields' => ['* * * *'],
            'too_many_fields' => ['* * * * * * *'],

            'invalid_minute' => ['60 * * * *'],
            'invalid_hour' => ['0 24 * * *'],
            'invalid_day_of_month' => ['0 0 32 * *'],
            'invalid_month' => ['0 0 * 13 *'],
            'invalid_day_of_week' => ['0 0 * * 8'],

            'step_zero' => ['*/0 * * * *'],
            'step_negative' => ['*/-5 * * * *'],

            'range_inverted' => ['0 10-5 * * *'],
            'range_out_of_bounds' => ['0 0-100 * * *'],

            'invalid_month_name' => ['0 0 * foo *'],
            'invalid_day_name' => ['0 0 * * bar'],

            'invalid_characters' => ['0 0 * * ?'],
        ];
    }
}
