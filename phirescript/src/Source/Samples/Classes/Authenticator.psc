<?php


namespace PHireScript\Classes;


use PHireScript\Classes\UserCredentials;
use PHireScript\Classes\Another;

interface Authenticator extends Another {
    public function authenticate(UserCredentials $credentials): bool;

    public function logout(): void;

}


