<?php

declare(strict_types=1);


namespace PHireScript\Sandbox\samples\success\case_61;


 class Arithmetic
{

    public function __construct(
        float $price,
        float $discount,
        int $count,
        int $base,
    ) {
        $this->price = $price;
        $this->discount = $discount;
        $this->count = $count;
        $this->base = $base;
        
    }
    public float $price;
    public float $discount;
    public int $count;
    public int $base;
    private function getTotal(): float{
$total = $this->price + $this->discount;
return $total;
}

    private function getMultiplied(): float{
$result = $this->price * 1.1;
return $result;
}

    private function getSubtracted(): float{
$diff = $this->price - $this->discount;
return $diff;
}

    private function getDivided(): float{
$half = $this->price / 2;
return $half;
}

    private function getMod(): int{
$rest = $this->count % 3;
return $rest;
}

    private function getPower(): float{
$squared = $this->price ** 2;
return $squared;
}

}

