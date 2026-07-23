<?php

declare(strict_types=1);


namespace PHireScript\Sandbox\samples\success\case_6;


use PHireScript\Runtime\Types\SuperTypes\Email;

 class UserImmutable
{

    public function __construct(
        int $id,
        string $username,
        string $email,
        bool $isAdmin,
        array|null $metadata,
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->email = Email::cast($email);
        $this->isAdmin = $isAdmin;
        $this->metadata = $metadata;
        
    }
    public int $id;
    public string $username;
    public string $email;
    public bool $isAdmin;
    public array|null $metadata;
}

