<?php


namespace PHireScript\Sandbox;


    // Alias for external package becomes namespace

use PDO;

    //Calls static method

$availableDrivers = PDO::getAvailableDrivers();
    // Execute a method on an instance (instance method called on class name)

$query = (new PDO())->query("SELECT id, name, email FROM user LIMIT 1");
    //Get the return via type propagation

$user = $query->fetchObject();
