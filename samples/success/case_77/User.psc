<?php

declare(strict_types=1);


namespace PHireScript\Sandbox\samples\success\case_77;


use PHireScript\Runtime\Types\MetaTypes\Date;

use PHireScript\Sandbox\samples\success\case_77\Entity;
use PHireScript\Sandbox\samples\success\case_77\Field;

#[Entity('User')]
 class User
{

    public function __construct(
        string $name,
        string $lastName,
        Date $born,
    ) {
        $this->name = $name;
        $this->lastName = $lastName;
        $this->born = $born instanceof Date ? $born : new Date($born);
        
    }
    #[Field(name: 'name', type: 'String', min: 3, max: 255)]
    public string $name;
    #[Field(name: 'lastName', type: 'String', min: 3, max: 255)]
    public string $lastName;
    #[Field(name: 'born', type: 'Date')]
    public Date $born;
}

