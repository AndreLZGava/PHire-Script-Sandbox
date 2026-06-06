<?php


namespace PHireScript\Sandbox\samples\success\case_50;


 class Counter
{

    public function __construct(
        int $count,
    ) {
        $this->count = $count;
        
    }
    public int $count;
    public function getCount(): int{
return $this->count;
}

}

