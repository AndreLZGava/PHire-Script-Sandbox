<?php

namespace PHireScript\Tests\Runtime\Types\SuperTypes;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHireScript\Runtime\Types\SuperTypes\Email;
use TypeError;

class EmailTest extends TestCase
{
    #[DataProvider('validEmails')]
    public function testCastValidEmails(mixed $input, string $expected): void
    {
        $result = Email::cast($input);

        $this->assertSame($expected, $result);
    }

    #[DataProvider('invalidEmails')]
    public function testCastInvalidEmails(mixed $input): void
    {
        $this->expectException(TypeError::class);

        Email::cast($input);
    }

    public static function validEmails(): array
    {
        return [
        'simple'              => ['test@example.com', 'test@example.com'],
        'with_dot'            => ['john.doe@example.com', 'john.doe@example.com'],
        'with_plus'           => ['john+tag@example.com', 'john+tag@example.com'],
        'subdomain'           => ['user@mail.example.com', 'user@mail.example.com'],
        'numeric_local'       => ['12345@example.com', '12345@example.com'],
        'dash_domain'         => ['user@my-domain.com', 'user@my-domain.com'],
        ];
    }

    public static function invalidEmails(): array
    {
        return [
        'missing_at'          => ['testexample.com'],
        'missing_domain'      => ['test@'],
        'missing_local'       => ['@example.com'],
        'double_at'           => ['test@@example.com'],
        'spaces'              => ['test @example.com'],
        'invalid_domain'      => ['test@example'],
        'empty_string'        => [''],
        'spaces_only'         => ['   '],
        'integer'             => [123],
        'boolean_true'        => [true],
        'array_input'         => [['test@example.com']],
        'object_input'        => [(object) []],
        ];
    }
}
