<?php
// Alias for external package becomes namespace, this can be used with any external PHP library, or internal from PHP
use PDO
//Calls static method, PHireScript has no differentiations on calling static or normal methods
// PHireScript must be able to validate in transpiler if a external class has the methods, properties and constants
// and if they are acessible or not. PHireScript won't compile external libraries, but it must validate that the generated
// code that uses it is running without problem and won't add bugs. This can be made by using cache, symble table,
//binder, checker and other strategies.
$availableDrivers = PDO::getAvailableDrivers();
//prints all results of drivers
\print_r($availableDrivers);
// Execute a method
$query = (new PDO())->query("SELECT id, name, email FROM user LIMIT 1");

//Get the return
$user = $query->fetchObject();
// if has user
if ($user) {
    // Obtain every property and then display it
    \print_r($user->id);
    \print_r($user->name);
    \print_r($user->email);
}
