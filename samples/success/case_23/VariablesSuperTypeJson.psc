<?php


use PHireScript\Runtime\Types\SuperTypes\Json;

    // Json super type not supporting expression for now

    // so it has to receive a string or a variable reference

$myArray = ['test' => 'test1'];

$variables = Json::cast(myArray);

$variablesObject = (object) ["test" => "Json"];

$byVariableReference = Json::cast(variablesObject);

$byString = Json::cast('{"this":1}');

$variablesReference = variables;

