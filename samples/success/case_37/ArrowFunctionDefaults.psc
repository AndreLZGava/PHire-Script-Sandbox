<?php


namespace PHireScript\Sandbox\samples\success\case_37;


$greet =  static function(String $name = "world"): string{
return $name;
}

;
$increment =  static function(Int $value = 0): int{
return $value;
}

;
$nullable =  static function(String $text = null): string{
return $text;
}

;
