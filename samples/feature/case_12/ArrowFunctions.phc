<?php
$tax = 0.15;
$calcTotal = fn($price, $rate) => $price * (1 + $rate);

$anotherFunction = function($price, $rate) {
  $rate = $rate * 0.15;
  return $price * (1 + $rate);
};

$product =(object) [];
$product->name = "Mechanic keyboard";
$product->price = 250.00;

$finalValue = calcTotal($product->price, $tax);

echo "Product: " . $product->name->toUpperCase();
echo "Value with tax: R$ " . $finalValue;
echo "Status: " . "processed"->toUpperCase();
