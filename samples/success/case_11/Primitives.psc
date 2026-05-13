<?php

// --- 1. String Type ---
$userName = "André";
$idAsString = (string) 12345;
// --- 2. Int Type ---
$userAge = 25;
$ageFromText = (int) "30";
// --- 3. Float Type ---
$productPrice = 250.99;
$taxValue = (float) "0.15";
// --- 4. Bool Type ---
$isUserActive = true;
$statusFromBinary = (bool) 1;
// --- 5. Array Type ---
$techStack = ["PHP", "PS", "TS"];
$singleItemArray = (array) $userName;
// --- 6. Object Type ---
$dataContainer = (object) ['id' => 1];
$myObject = (object) ['test' => "test"];
$objFromMap = (object) ['id' => 1];
/**
 * Example comment
 */
