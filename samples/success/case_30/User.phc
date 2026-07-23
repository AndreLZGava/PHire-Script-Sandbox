<?php

declare(strict_types=1);


namespace PHireScript\Sandbox\samples\success\case_30;


use PHireScript\Runtime\Types\SuperTypes\Email;

use PHireScript\Sandbox\samples\success\case_30\UserCredentials;
use PHireScript\Sandbox\samples\success\case_30\Another;

 class User
{

    public function __construct(
        int $id,
        string $username,
        string $email,
        bool $isAdmin,
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->email = Email::cast($email);
        $this->isAdmin = $isAdmin;
        
    }
    public int $id;
    public string $username;
    public string $email;
    public bool $isAdmin = true;
}

