<?php

declare(strict_types=1);


namespace PHireScript\Sandbox\samples\success\case_28;


use PHireScript\Sandbox\samples\success\case_28\UserCredentials;
use PHireScript\Sandbox\samples\success\case_28\User as UserAccess;
use PHireScript\Sandbox\samples\success\case_28\Authenticator;
use PHireScript\Sandbox\samples\success\case_28\Another;
use PHireScript\Sandbox\samples\success\case_28\Logger;

use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;

 class AuthenticatorClass implements Authenticator, Another
{
    use Logger;
    public function save(Array $data): bool{
return true;
}

    public function delete(): void{
return ;
}

    public function getCompleteUserName(): string|null{
return null;
}

    public function authenticate(Null|UserCredentials $credentials = null): bool{
return true;
}

    public function logout(): void{
return ;
}

    public function returnNull(): null{
return null;
}

    public function returnStringSingle(): string{
return 'single quotes';
}

    public function returnStringDouble(): string{
return "double quotes";
}

    public function returnFloat(): float{
return 15.2;
}

    public function returnInt(): int{
return 10;
}

    public function returnArrayEmpty(): array{
return [

];
}

    public function returnArrayComplete(): array{
return [
'example' => [
'another', 
'array'
]
];
}

    public function returnObjectEmpty(): object{
return (object) [];
}

    public function returnObject(): object{
return (object) ['test' => 1];
}

}

