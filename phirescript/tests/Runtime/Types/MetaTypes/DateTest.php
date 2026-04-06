<?php

namespace PHireScript\Tests\Runtime\Types\MetaTypes;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHireScript\Runtime\Types\MetaTypes\Date;
use TypeError;
use DateTimeImmutable;
use DateTime;
use PHireScript\Helper\Debug\Debug;

class DateTest extends TestCase
{
    #[DataProvider('invalidDatesProvider')]
    public function testConstructInvalidDates(mixed $input): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage("Date format invalid!");

        new Date($input);
    }

    #[DataProvider('validDatesProvider')]
    public function testConstructValidDates(mixed $input, string $expectedIso): void
    {
        $date = new Date($input);

        $this->assertSame($expectedIso, $date->format('iso'));
    }

    public function testMagicGetters(): void
    {
        $date = new Date('2023-12-25');

        $this->assertSame(2023, $date->year);
        $this->assertSame(12, $date->month);
        $this->assertSame(25, $date->day);

        $expectedTimestamp = (new DateTimeImmutable('2023-12-25'))->getTimestamp();
        $this->assertSame($expectedTimestamp, $date->timestamp);

        $this->assertNull($date->nonExistentProperty);
    }

    #[DataProvider('formatsProvider')]
    public function testFormatting(string $style, string $expectedOutput): void
    {
        $date = new Date('2023-05-07');
        $this->assertSame($expectedOutput, $date->format($style));
    }

    public function testToStringMagicMethod(): void
    {
        $date = new Date('2023-01-01');

        $this->assertSame('2023-01-01', (string) $date);
    }

    public function testDefaultConstructorIsNow(): void
    {
        $date = new Date();
        $phpDate = new DateTimeImmutable('now');

        $this->assertSame($phpDate->format('Y-m-d'), $date->format('iso'));

        $date = new Date('');
        $phpDate = new DateTimeImmutable('now');

        $this->assertSame($phpDate->format('Y-m-d'), $date->format('iso'));
    }

    public static function validDatesProvider(): array
    {
        return [
        'iso_string'           => ['2023-10-05', '2023-10-05'],
        'br_format'            => ['05/10/2023', '2023-10-05'],
        'timestamp_int'        => [1696464000, '2023-10-05'],
        'datetime_immutable'   => [new DateTimeImmutable('2023-10-05'), '2023-10-05'],
        'datetime_mutable'     => [new DateTime('2023-10-05'), '2023-10-05'],
        'slash_format_reverse' => ['2023/10/05', '2023-10-05'],
        'overflow_date'        => ['35/10/2023', '2023-11-04'],
        ];
    }

    public static function invalidDatesProvider(): array
    {
        return [
        'random_string'       => ['banana'],
        'invalid_month'       => ['2023-13-01'],
        'array_input'         => [[2023]],
        'object_generic'      => [(object)['date' => '2023']],
        'boolean_true'        => [true],
        'boolean_false'       => [false],
        'null_value'          => [null],
        ];
    }

    public static function formatsProvider(): array
    {
        return [
        'iso'      => ['iso', '2023-05-07'],
        'br'       => ['br', '07/05/2023'],
        'human'    => ['human', '7 de May de 2023'],
        'custom'   => ['d-m-Y', '07-05-2023'],
        ];
    }
}
