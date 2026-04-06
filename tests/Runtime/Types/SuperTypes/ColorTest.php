<?php

namespace PHireScript\Tests\Runtime\Types\SuperTypes;

use PHPUnit\Framework\TestCase;
use PHireScript\Runtime\Types\SuperTypes\Color;
use PHPUnit\Framework\Attributes\DataProvider;
use TypeError;

class ColorTest extends TestCase
{
    #[DataProvider('validColors')]
    public function testCastValidColors(mixed $input): void
    {
        $result = Color::cast($input);

        $this->assertEquals('#FFFFFF', $result);
    }

    #[DataProvider('invalidColors')]
    public function testCastInvalidColors(mixed $input): void
    {
        $this->expectException(TypeError::class);
        Color::cast($input);
    }

    public static function validColors(): array
    {
        return [
        'valid_with_hashtag_tree_lower' => ['#fff'],
        'valid_without_hashtag_tree_lower' => ['fff'],
        'valid_with_hashtag_tree_upper' => ['#FFF'],
        'valid_without_hashtag_tree_upper' => ['FFF'],
        'valid_with_hashtag_six_lower' => ['#ffffff'],
        'valid_without_hashtag_six_lower' => ['ffffff'],
        'valid_with_hashtag_six_upper' => ['#FFFFFF'],
        'valid_without_hashtag_six_upper' => ['FFFFFF'],
        ];
    }

    public static function invalidColors(): array
    {
        return [
        'invalid_array' => [['#fff']],
        'invalid_bool' => [true],
        'invalid_number' => [12],
        'invalid_object' => [(object) []],
        'invalid_string' => ['any_other_thing'],
        ];
    }
}
