<?php

declare(strict_types=1);


namespace PHireScript\Sandbox\samples\success\case_56;


use PHireScript\Runtime\Types\SuperTypes\Email;

 class Setter
{

    public function __construct(
        string $email,
        string $username,
    ) {
        $this->email = Email::cast($email);
        $this->username = $username;
        
    }
    public string $email;
    public string $username;
    public function setEmail(string $email): void
    {
        $this->email = Email::cast($email);
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

}

