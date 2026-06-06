<?php


namespace PHireScript\Sandbox\samples\success\case_30;


use PHireScript\Runtime\Types\SuperTypes\Email;
use PHireScript\Runtime\Types\MetaTypes\Date;
use PHireScript\Runtime\Types\UnionType;
use PHireScript\Runtime\Types\SuperTypes\Ipv4;
use PHireScript\Runtime\Types\SuperTypes\Ipv6;

    // 1. The Type (Data Shape) can be converted into a class in the future

 class UserCredentials
{

    public function __construct(
        string $login,
        string $userEmail,
        Date $dateBirth,
        string $lastIp,
    ) {
        $this->login = $login;
        $this->userEmail = Email::cast($userEmail);
        $this->dateBirth = $dateBirth instanceof Date ? $dateBirth : new Date($dateBirth);
        $this->lastIp = UnionType::cast($lastIp, [Ipv4::class, Ipv6::class]);
        
    }
    /**
    * String corresponds to the variable type (String, Int, Float, Bool, Object, Array are PHP primitives
    * but here they will be treated as separate objects to allow method chaining)
    * 'login' is the variable name; inside a type, 'var' is not required.
    */
    public string $login;
    // Email will be a super type as Primitive string. It is a string but will be validated
    // by a regex like a new class Email
    public string $userEmail;
    // Same as email but this will use date time functions/protected PHP class
    protected Date $dateBirth;
    // Like Email, this will be a string for PHP but will be validated as an IPV4 or IPV6
    // class with regex. # indicates private
    private string $lastIp;
}

