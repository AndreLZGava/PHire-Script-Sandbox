<?php

namespace PHireScript\Tests\Runtime\Types\SuperTypes;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHireScript\Runtime\Types\SuperTypes\Slug;
use TypeError;

class SlugTest extends TestCase
{
    #[DataProvider('validSlugs')]
    public function testCastValidSlugs(mixed $input, string $expected): void
    {
        $result = Slug::cast($input);

        $this->assertSame($expected, $result);
    }

    #[DataProvider('invalidSlugs')]
    public function testCastInvalidSlugs(mixed $input): void
    {
        $this->expectException(TypeError::class);

        Slug::cast($input);
    }

    public static function validSlugs(): array
    {
        return [
        'simple'                    => ['hello world', 'hello-world'],
        'multiple_spaces'           => ['hello   world', 'hello-world'],
        'uppercase'                 => ['Hello World', 'hello-world'],
        'leading_trailing_spaces'   => ['  hello world  ', 'hello-world'],
        'accents'                   => ['ação rápida', 'acao-rapida'],
        'mixed_accents'             => ['Café com Açúcar', 'cafe-com-acucar'],
        'symbols_removed'           => ['hello @ world!', 'hello-world'],
        'multiple_hyphens'          => ['hello---world', 'hello-world'],
        'numbers'                   => ['post 123', 'post-123'],
        'already_slug'              => ['already-a-slug', 'already-a-slug'],
        'unicode_chars'             => ['São_Paulo#2024', 'sao-paulo-2024'],
        ];
    }

    public static function invalidSlugs(): array
    {
        return [
        'empty_string'        => [''],
        'spaces_only'         => ['   '],
        'symbols_only'        => ['@@@'],
        'hyphens_only'        => ['---'],
        'non_string_int'      => [123],
        'boolean_true'        => [true],
        'array_input'         => [['hello world']],
        'object_input'        => [(object) []],
        ];
    }
}
