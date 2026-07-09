<?php

declare(strict_types=1);


namespace PHireScript\Sandbox\samples\success\case_38;


$discount = 0.1;
$applyDiscount =  static function(Float $price) use ($discount): float{
return $discount;
}

;
$greeting = "Hello";
$buildMessage =  static function(String $name) use ($greeting): string{
return $greeting;
}

;
$noCapture =  static function(Int $n): int{
return $n;
}

;
