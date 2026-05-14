<?php


namespace PHireScript\Classes;


use PHireScript\Runtime\Types\SuperTypes\Email;

use PHireScript\Classes\UserCredentials;
use PHireScript\Classes\Another;

 class User {
    public int $id;
    public string $username;
    public string $email;
    public bool $isAdmin = True;
    public null|array $metadata = Null;

    public function __construct(
        int $id,
        string $username,
        string $email,
        bool $isAdmin,
        null|array $metadata,
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->email = Email::cast($email);
        $this->isAdmin = $isAdmin;
        $this->metadata = $metadata;
        
    }
}

