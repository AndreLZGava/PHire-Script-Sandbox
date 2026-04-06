<?php


namespace PHireScript\Classes;


use PHireScript\Classes\UserCredentials;
use PHireScript\Classes\User as UserAccess;
use PHireScript\Classes\Authenticator;
use PHireScript\Classes\Another;
use PHireScript\Classes\Logger;

use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;

 class AuthenticatorClass implements Authenticator, Another {
    use Logger;
    public function authenticate(UserCredentials $credentials): bool {
        return true;
    }

    public function logout(): void {
        return ;
    }

    public function returnNull(): null {
        return Null;
    }

    public function returnStringSingle(): string {
        return 'single quotes';
    }

    public function returnStringDouble(): string {
        return "double quotes";
    }

    public function returnFloat(): float {
        return 15.2;
    }

    public function returnInt(): int {
        return 10;
    }

    public function returnArrayEmpty(): array {
        return [];
    }

    public function returnArrayComplete(): array {
        return ['example' => ['another', 'array']];
    }

    public function returnObjectEmpty(): object {
        return (object) [];
    }

    public function returnObject(): object {
        return (object) ["test" => 1];
    }

}

