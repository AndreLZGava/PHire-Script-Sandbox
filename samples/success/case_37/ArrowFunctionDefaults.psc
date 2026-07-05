<?php


namespace PHireScript\Sandbox;


$greet =  function(String $name = "world"): string{
return $name;
}

;
$increment =  function(Int $value = 0): int{
return $value;
}

;
$nullable =  function(String $text = null): string{
return $text;
}

;
