<?php


namespace PHireScript\Classes;



abstract class AbstractClass {
    public string $tableName;

    public function __construct(
        
    ) {
        
        if (!isset($this->tableName)) {
            throw new \LogicException("Property tableName must be initialized.");
        }
    }
    public function methodExample(): null {
        return Null;
    }

}

