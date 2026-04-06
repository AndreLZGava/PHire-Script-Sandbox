<?php


namespace PHireScript\Classes;


use PHireScript\Runtime\Types\SuperTypes\Email;
use PHireScript\Runtime\Types\MetaTypes\Date;
use PHireScript\Runtime\Types\UnionType;
use PHireScript\Runtime\Types\SuperTypes\Ipv4;
use PHireScript\Runtime\Types\SuperTypes\Ipv6;

    // 1. The Type (Data Shape) can be converted into a class in the future

 class UserCredentials {
    public string $login;
    public string $userEmail;
    protected Date $dateBirth;
    private string $lastIp;

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
}

