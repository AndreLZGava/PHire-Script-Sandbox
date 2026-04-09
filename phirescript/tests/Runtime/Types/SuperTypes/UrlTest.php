<?php

namespace PHireScript\Tests\Runtime\Types\SuperTypes;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHireScript\Runtime\Types\SuperTypes\Url;
use TypeError;

class UrlTest extends TestCase
{
    #[DataProvider('validUrls')]
    public function testCastValidUrls(mixed $input, string $expected): void
    {
        $result = Url::cast($input);

        $this->assertSame($expected, $result);
    }

    #[DataProvider('invalidUrls')]
    public function testCastInvalidUrls(mixed $input): void
    {
        $this->expectException(TypeError::class);

        Url::cast($input);
    }

    public static function validUrls(): array
    {
        return [
        'http' => [
        'http://example.com',
        'http://example.com',
        ],
        'https' => [
        'https://example.com',
        'https://example.com',
        ],
        'with_www' => [
        'https://www.example.com',
        'https://www.example.com',
        ],
        'with_path' => [
        'https://example.com/path/to/resource',
        'https://example.com/path/to/resource',
        ],
        'with_query' => [
        'https://example.com/search?q=test',
        'https://example.com/search?q=test',
        ],
        'with_fragment' => [
        'https://example.com/page#section',
        'https://example.com/page#section',
        ],
        'with_port' => [
        'https://example.com:8080',
        'https://example.com:8080',
        ],
        'subdomain' => [
        'https://api.example.co.uk',
        'https://api.example.co.uk',
        ],
        'ip_host' => [
        'http://127.0.0.1',
        'http://127.0.0.1',
        ],
        'ipv6_host' => [
        'http://[::1]',
        'http://[::1]',
        ],
        'trimmed_spaces' => [
        '   https://example.com  ',
        'https://example.com',
        ],
        ];
    }

    public static function invalidUrls(): array
    {
        return [
        'missing_scheme' => [
        'example.com',
        ],
        'relative_path' => [
        '/path/to/resource',
        ],
        'only_scheme' => [
        'http://',
        ],
        'only_scheme_with_slashes' => [
        'http:///',
        ],
        'mailto_scheme' => [
        'mailto:test@example.com',
        ],
        'javascript_scheme' => [
        'javascript:alert(1)',
        ],
        'ftp_without_host' => [
        'ftp://',
        ],
        'invalid_host' => [
        'http://-example.com',
        ],
        'spaces_only' => [
        '   ',
        ],
        'empty_string' => [
        '',
        ],
        'null' => [
        null,
        ],
        'int_input' => [
        12345,
        ],
        'boolean_true' => [
        true,
        ],
        'array_input' => [
        ['https://example.com'],
        ],
        'object_input' => [
        (object) [],
        ],
        ];
    }
}
