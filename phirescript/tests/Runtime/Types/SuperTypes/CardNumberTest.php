<?php

namespace PHireScript\Tests\Runtime\Types\SuperTypes;

use PHPUnit\Framework\TestCase;
use PHireScript\Runtime\Types\SuperTypes\CardNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use TypeError;

class CardNumberTest extends TestCase
{
    #[DataProvider('invalidCardProvider')]
    public function testCastInvalidCardNumbers(mixed $input): void
    {
        $this->expectException(TypeError::class);
        CardNumber::cast($input);
    }

    #[DataProvider('validCardProvider')]
    public function testCastValidCardNumbers(mixed $input, string $expected): void
    {
        $result = CardNumber::cast($input);

        $this->assertEquals($expected, $result, "Failed to cast valid card: $input");
    }

  /**
   * Test transformation logic independently if needed,
   * but cast() already covers it via transform()
   */
    public function testTransformRemovesNonDigits(): void
    {
      // Using reflection or a public proxy if you want to test protected methods,
      // but testing through cast() is the standard "Black Box" approach.
        $this->assertEquals('4111111111111111', CardNumber::cast('4111-1111-1111-1111'));
    }

  /**
   * Data Provider for valid cards
   * Includes real Luhn-compliant numbers
   */
    public static function validCardProvider(): array
    {
        return [
        'visa_16_digits' => [
        'input'    => '4111 1111 1111 1111',
        'expected' => '4111111111111111',
        ],

        'visa_13_digits' => [
        'input'    => '4222-2222-2222-2',
        'expected' => '4222222222222',
        ],

        'mastercard_format' => [
        'input'    => '5105 1051 0510 5100',
        'expected' => '5105105105105100',
        ],

        'visa_mixed_format' => [
        'input'    => '4012-8888 8888-1881',
        'expected' => '4012888888881881',
        ],

        'max_length_19_digits' => [
        'input'    => '4000 0000 0000 0000 006',
        'expected' => '4000000000000000006',
        ],
        ];
    }

  /**
   * Data Provider for invalid cards
   */
    public static function invalidCardProvider(): array
    {
        return [
        'array_input'         => ['input' => ['49927398716']],
        'invalid_luhn'        => ['input' => '49927398717'], // Sum doesn't match
        'too_short'           => ['input' => '1234567890'],
        'too_long'            => ['input' => '12345678901234567890123'],
        'empty_string'        => ['input' => ''],
        'non_numeric_chars'   => ['input' => '4992-AAAA-716'], // After transform, might fail length
        'wrong_data_type'     => ['input' => 12345678901234], // Testing integer input
        'null_value'          => ['input' => null],
        ];
    }
}
