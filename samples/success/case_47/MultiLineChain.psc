<?php


namespace App;


$mystring = 'this is a string';
$result = \strlen(\mb_strtoupper(\str_replace('is', 'was', $mystring), 'UTF-8'));
