<?php

namespace PHireScript\Tests\Runtime\Types\MetaTypes;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHireScript\Runtime\Types\MetaTypes\Currency;
use Exception;
use PHireScript\Helper\Debug\Debug;

class CurrencyTest extends TestCase
{
    #[DataProvider('validInputsProvider')]
    public function testConstructAndTransform(mixed $input, int $expectedCents, float $expectedDecimal): void
    {
        $money = new Currency($input, 'BRL');
        $this->assertSame($expectedCents, $money->cents);
        $this->assertEquals($expectedDecimal, $money->decimal);
    }

    public function testDefaultCurrencyAndConstructionFromInstance(): void
    {
        $original = new Currency(100, 'EUR');
        $new = new Currency($original);

        $this->assertSame(100, $new->cents);
        $this->assertSame('EUR', $new->currency);

        $default = new Currency(50);
        $this->assertSame('USD', $default->currency, 'Default currency should be USD');
    }

    public function testMagicGetters(): void
    {
        $money = new Currency(1234.56, 'BRL');

        $this->assertSame(123456, $money->cents);
        $this->assertEquals(1234.56, $money->decimal);
        $this->assertSame('BRL', $money->currency);
        $this->assertNull($money->nonExistent);
    }

    public function testConvertTo(): void
    {
        $brl = new Currency(100.00, 'BRL');

        $usd = $brl->convertTo('USD', 0.20);

        $this->assertSame('USD', $usd->currency);
        $this->assertSame(2000, $usd->cents);
        $this->assertEquals(20.00, $usd->decimal);
    }

    public function testAddSameCurrency(): void
    {
        $c1 = new Currency(10.50, 'BRL');
        $c2 = new Currency(5.50, 'BRL');

        $sum = $c1->add($c2);

        $this->assertSame(1600, $sum->cents);
        $this->assertSame('BRL', $sum->currency);
    }

    public function testAddDifferentCurrenciesThrowsException(): void
    {
        $c1 = new Currency(10, 'BRL');
        $c2 = new Currency(10, 'USD');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Not possible sum different currencies");

        $c1->add($c2);
    }

    #[DataProvider('formatProvider')]
    public function testFormatting(mixed $value, string $currency, string $locale, string $expected): void
    {
        $money = new Currency($value, $currency);
        $this->assertStringContainsString(
            preg_replace('/\s+/', '', $expected),
            preg_replace('/\s+/', '', $money->format($locale))
        );
    }

    public function testToStringUsesDefaultFormat(): void
    {
        $money = new Currency(10, 'BRL');
        $this->assertStringContainsString('10,00', (string)$money);
    }

    public static function validInputsProvider(): array
    {
        return [
            'string_us'          => ['1,250.50', 125050, 1250.50],
            'string_br'          => ['1.250,50', 125050, 1250.50],
            'float_value'        => [19.99, 1999, 19.99],
            'integer_cents'      => [1000, 1000, 10.00],
            'string_clean'       => ['100', 10000, 100.00],
            'string_with_symbol' => ['R$ 50,25', 5025, 50.25],
            'round_up'           => [10.555, 1056, 10.56],
            'invalid_fallback'   => ['abc', 0, 0.00],
        ];
    }

    public static function formatProvider(): array
    {
        return [
            'brl_pt_br' => [1500.50, 'BRL', 'pt_BR', 'R$ 1.500,50'],
            'usd_en_us' => [1500.50, 'USD', 'en_US', '$1,500.50'],
            'eur_fr_fr' => [1500.50, 'EUR', 'fr_FR', '1 500,50 €'],
        ];
    }
}
