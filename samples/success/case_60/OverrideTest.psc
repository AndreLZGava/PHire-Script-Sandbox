<?php

declare(strict_types=1);


namespace PHireScript\Sandbox\samples\success\case_60;


 class OverrideTest
{

    public function __construct(
        int $id,
    ) {
        $this->id = $id;
        
    }
    public int $id;
    public function getId(): int{
return $this->id;
}

}

