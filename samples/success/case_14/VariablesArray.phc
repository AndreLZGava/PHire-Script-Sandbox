<?php

declare(strict_types=1);


    /**
*  This are the examples of array cases
*/

    //scopedArray = Array<String>

    //scopedArray = []

    //scopedArray[] = 'test'

    // Literal inline array (only supported inline for now)

$myTest = 'guess';
$anotherOne = 'another One';
$variables = [
true, 
'another', 
'test' => [
'array'
], 
0 => 'another test', 
$myTest => $anotherOne
];
    // Array cast

$variables2 = (Array)'test';
$variables['MDS'] = 'teste';

$variables['omg'] = [
'test' => 0
];

unset($variables['teste']);

$variables3 = [
'test' => [
'mds'
]
];
\print_r($variables3);

$variable4 = [
0 => [
'#drupal' => [
'#shora' => 'neee'
]
], 
1 => [
'#elements' => [
'#ttttt' => 'Shoke de monstro'
]
]
];
    // this makes me understand that there is a difference between a Function call

    // resolver for assignment and other situations, because if you are

    // doing an assign you do not override the variable

$newVarBool = \in_array('another', $variables);
    // Variable reference

$variablesReference = $variables;
