<?php

namespace PHireScript\Tests\Runtime\Types\MetaTypes;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHireScript\Runtime\Types\MetaTypes\Password;
use InvalidArgumentException;
use PHireScript\Helper\Debug\Debug;
use PHPUnit\Event\Test\Passed;

class PasswordTest extends TestCase
{
    #[DataProvider('invalidPasswordsProvider')]
    public function testConstructInvalidPasswords(string $input, string $expectedErrorPart): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedErrorPart);

        new Password($input);
    }

    #[DataProvider('validPasswordsProvider')]
    public function testConstructValidPasswords(string $input): void
    {
        $password = new Password($input);

        $this->assertStringStartsWith('$2y$', $password->getHash());
        $this->assertTrue($password->verify($input));
    }

    public function testConstructFromExistingHash(): void
    {
        $existingHash = '$2y$10$DkP7of.7rNG26f98lcUcM.B2Psqkeuk7SBhKxv9qF9/jHo461HL26';

        $password = new Password($existingHash);

        $this->assertSame($existingHash, $password->getHash());
        $this->assertTrue($password->verify('Passord@123'));
    }

    public function testVerifyMethod(): void
    {
        $plain = "SafePass@123";
        $password = new Password($plain);

        $this->assertTrue($password->verify($plain));
        $this->assertFalse($password->verify("wrong_password"));
        $this->assertFalse($password->verify("safepass@123"));
    }

    public function testToStringHidesActualPassword(): void
    {
        $password = new Password("Complex@987");

        $this->assertSame('********', (string) $password);
    }

    public function testCheckStrengthStaticMethod(): void
    {
        $errors = Password::checkStrength('123');

        $this->assertIsArray($errors);
        $this->assertContains("at least 8 characters expected", $errors);
        $this->assertContains("must contain 1 uppercase letters", $errors);
        $this->assertContains("must contain 1 symbols", $errors);
    }

    public function testExtendPassord(): void
    {
        $strongPasswordClass = (new class ('') extends Password {
            protected static int $minLength = 16;
            protected static int $minNumbers = 2;
            protected static int $minSymbols = 2;
            protected static int $minUpper = 2;
            public function __construct(string $plainTextOrHash = "")
            {
                if ($plainTextOrHash !== "") {
                    parent::__construct($plainTextOrHash);
                }
            }
        })::class;

        $validStrong = "Strong@Pass#12345";
        $instance = new $strongPasswordClass($validStrong);

        $this->assertInstanceOf(Password::class, $instance);
        $this->assertTrue($instance->verify($validStrong));
        $this->assertStringStartsWith('$2y$', $instance->getHash());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("at least 16 characters expected");

        new $strongPasswordClass("ShortPass@123");
    }

    public static function validPasswordsProvider(): array
    {
        return [
        'standard_valid' => ['P@ssword123'],
        'long_valid'     => ['Valid_Password_With_99_Numbers!'],
        'symbols_galore' => ['A1#b2$C3%D4^'],
        ];
    }

    public static function invalidPasswordsProvider(): array
    {
        return [
        'too_short' => [
        'Ab1!',
        'at least 8 characters expected'
        ],
        'no_numbers' => [
        'Password@Symbol',
        'must contain 1 numbers'
        ],
        'no_uppercase' => [
        'password@123',
        'must contain 1 uppercase letters'
        ],
        'no_symbols' => [
        'Password123',
        'must contain 1 symbols'
        ],
        'multiple_errors' => [
        'short',
        'at least 8 characters expected, must contain 1 numbers,' .
          ' must contain 1 uppercase letters, must contain 1 symbols'
        ],
        ];
    }
}
