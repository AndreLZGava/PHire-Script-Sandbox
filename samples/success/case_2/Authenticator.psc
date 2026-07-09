<?php

declare(strict_types=1);


namespace PHireScript\Sandbox\samples\success\case_2;


use PHireScript\Sandbox\samples\success\case_2\UserCredentials;
use PHireScript\Sandbox\samples\success\case_2\Another;

interface Authenticator extends Another
{
public function authenticate(UserCredentials $credentials): bool;

public function logout(): void;

}

