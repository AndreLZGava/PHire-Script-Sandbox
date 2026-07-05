<?php


namespace PHireScript\Sandbox;


$mystring = 'this is a test string';
$parts = \explode(' ', $mystring, 9223372036854775807);
$count = \count($parts);
$chainedCount = \count(\explode(' ', $mystring, 9223372036854775807));
