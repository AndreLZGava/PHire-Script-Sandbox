<?php

declare(strict_types=1);


namespace PHireScript\Sandbox\samples\success\case_77;


#[\Attribute]
class Entity
{
    public function __construct(
        public string $name
    ) {}
}

