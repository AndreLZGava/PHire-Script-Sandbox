<?php


namespace PHireScript\Sandbox;


$discount = 0.1;
$applyDiscount =  function(Float $price) use ($discount): float{
return $discount;
}

;
$greeting = "Hello";
$buildMessage =  function(String $name) use ($greeting): string{
return $greeting;
}

;
$noCapture =  function(Int $n): int{
return $n;
}

;
