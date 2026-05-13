<?php

// Empty object
$variables3 = (object) ['test' => 1];
$variables = (object) [];
// Casting object
$variables2 = (object) ['array' => 'this was an array', 'test' => ['new test']];
// Object inline
$variables3 = (object) ['test' => 1, 'anotherReference' => $variables2, 'name' => "Example"];
// Object reference
$variablesReference = $variables;
