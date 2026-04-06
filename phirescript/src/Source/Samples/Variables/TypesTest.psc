<?php
$fruits = ["apple", "banana"];
$fruits->push("grape");
$fruits->push("orange");

$user = "William";
$nick = $user->toUpperCase();

$listOfFruits = $fruits->join(" - ");

$counter = 10;
$counter = ["one", "two"];
$counter->push("three");

echo "Fruits: " . $listOfFruits;
echo "User: " . $nick;
echo "Counter: " . $counter->join(" | ");
