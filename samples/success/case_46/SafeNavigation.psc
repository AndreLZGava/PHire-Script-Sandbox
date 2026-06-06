<?php


namespace App;


$mystring = 'this is a test string';
$result = ($__chain_0 = (function() use ($mystring) {
    $__start = \mb_strpos($mystring, 'this');
    if ($__start === false) return null;
    $__start += \mb_strlen('this');
    $__end = \mb_strpos($mystring, 'string', $__start);
    if ($__end === false) return null;
    return \mb_substr($mystring, $__start, $__end - $__start, "UTF-8");
})()) !== null ? \strlen($__chain_0) : null;
