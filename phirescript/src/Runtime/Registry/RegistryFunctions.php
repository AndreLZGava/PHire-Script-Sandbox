<?php

namespace PHireScript\Runtime\Registry;

class FunctionRegistry
{
    private static $functions = [];

    public static function registerFunction($name, $callable)
    {
        self::$functions[$name] = $callable;
    }

    public static function getFunction($name)
    {
        if (isset(self::$functions[$name])) {
            return self::$functions[$name];
        }
        return null;
    }

    public static function listFunctions()
    {
        return array_keys(self::$functions);
    }
}
