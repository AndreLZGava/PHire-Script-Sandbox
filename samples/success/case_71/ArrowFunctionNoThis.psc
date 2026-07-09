<?php


namespace PHireScript\Sandbox\samples\success\case_71;


$double =  static function(Int $n): int{
return $n;
}

;
$multiplier = 3;
$scale =  static function(Int $n) use ($multiplier): int{
return $multiplier;
}

;
