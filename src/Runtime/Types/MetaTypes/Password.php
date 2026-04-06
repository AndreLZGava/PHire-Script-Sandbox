<?php

declare(strict_types=1);

namespace PHireScript\Runtime\Types\MetaTypes;

use PHireScript\Runtime\Types\MetaTypes;

class Password extends MetaTypes
{
    protected string $hash;

    protected static int $minLength = 8;
    protected static int $minNumbers = 1;
    protected static int $minSymbols = 1;
    protected static int $minUpper = 1;

    public function __construct(string $plainTextOrHash)
    {
        if (str_starts_with($plainTextOrHash, '$2y$')) {
            $this->hash = $plainTextOrHash;
            return;
        }

        $errors = self::checkStrength($plainTextOrHash);

        if (!empty($errors)) {
            throw new \InvalidArgumentException("Password not enough: " . \implode(", ", $errors));
        }

        $this->hash = password_hash($plainTextOrHash, PASSWORD_BCRYPT);
    }

    public static function checkStrength(string $pwd): array
    {
        $errors = [];

        if (strlen($pwd) < static::$minLength) {
            $errors[] = "at least " . static::$minLength . " characters expected";
        }

        if (preg_match_all('/[0-9]/', $pwd) < static::$minNumbers) {
            $errors[] = "must contain " . static::$minNumbers . ' numbers';
        }

        if (preg_match_all('/[A-Z]/', $pwd) < static::$minUpper) {
            $errors[] = "must contain " . static::$minUpper . ' uppercase letters';
        }

        if (preg_match_all('/[^A-Za-z0-9]/', $pwd) < static::$minSymbols) {
            $errors[] = "must contain " . static::$minSymbols . ' symbols';
        }

        return $errors;
    }

    public function verify(string $attempt): bool
    {
        return password_verify($attempt, $this->hash);
    }

    public function __toString(): string
    {
        return "********";
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    protected static function transform(mixed $value): mixed
    {
        return $value;
    }

    protected static function validate(mixed $value): bool
    {
        return true;
    }
}
