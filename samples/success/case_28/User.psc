<?php


namespace PHireScript\Sandbox\samples\success\case_28;


use PHireScript\Runtime\Types\SuperTypes\Email;

use PHireScript\Sandbox\samples\success\case_28\UserCredentials;
use PHireScript\Sandbox\samples\success\case_28\Another;

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

