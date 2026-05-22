<?php


namespace PHireScript\Sandbox\samples\success\case_28;


use PHireScript\Sandbox\samples\success\case_28\UserCredentials;
use PHireScript\Sandbox\samples\success\case_28\Another;

interface Authenticator extends Another
{
public function authenticate(UserCredentials $credentials): bool;

public function logout(): void;

}

