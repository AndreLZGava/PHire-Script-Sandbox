<?php

declare(strict_types=1);


namespace PHireScript;


use PHireScript\Runtime\Types\MetaTypes\Date;

use PHireScript\Auditable;

#[\AllowDynamicProperties]
 class Other
{

    public function __construct(
        string $name,
        string $password,
        Date $oldPassword,
    ) {
        $this->name = $name;
        $this->password = $password;
        $this->oldPassword = $oldPassword instanceof Date ? $oldPassword : new Date($oldPassword);
        
    }
    public string $name;
    public string $password;
    #[Auditable(reason: 'legacy field')]
    #[\Deprecated]
    public Date $oldPassword;
    #[\ReturnTypeWillChange]
    public function convertOldPasswordToNewOne(): string{
return '';
}

}

