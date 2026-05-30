<?php
// Alias for external package becomes namespace
use DateTime as DateTimePhp;
//Call static method as normal method, transpiler must handle the conversion (symbol table, binder, checker parser)
$date = DateTimePhp::createFromFormat('d/m/Y', '25/12/2023');
//Calls constants as property of class and call normal method with . which will become -> in PHP
\print_r($date->format(DateTimePhp::ATOM));
