<?php

declare(strict_types=1);


namespace PHireScript\Sandbox\samples\success\case_76;


 class Pricing
{

    public function __construct(
        float $price,
        float $taxRate,
    ) {
        $this->price = $price;
        $this->taxRate = $taxRate;
        
    }
    public float $price;
    public float $taxRate;
    private function getPrice(): float{
return $this->price;
}

    private function getTaxRate(): float{
return $this->taxRate;
}

    private function priceWithTax(): float{
$result = $this->getPrice() * (1 + $this->getTaxRate());
return $result;
}

    private function discount(): float{
$result = $this->getPrice() - $this->getPrice() * $this->getTaxRate();
return $result;
}

}

