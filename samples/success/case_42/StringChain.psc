<?php


namespace App;


$mystring = 'this is a string';
$processed = \strlen(\str_replace('is', 'is really', $mystring));
$upper = \mb_strtoupper($mystring, 'UTF-8');
$chainThree = \strlen(\mb_strtoupper(\str_replace('is', 'was', $mystring), 'UTF-8'));
