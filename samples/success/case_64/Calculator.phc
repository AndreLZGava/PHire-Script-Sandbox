<?php

declare(strict_types=1);


namespace PHireScript\Sandbox\samples\success\case_64;


 class Calculator
{

    public function __construct(
        int $base,
        float $rate,
    ) {
        $this->base = $base;
        $this->rate = $rate;
        
    }
    public int $base;
    public float $rate;
    private function getBase(): int{
return $this->base;
}

    private function getRate(): float{
return $this->rate;
}

    private function total(): float{
$result = $this->getBase() * $this->getRate();
return $result;
}

    private function withBonus(): float{
$result = ($this->getBase() + 10) * $this->getRate();
return $result;
}

}

