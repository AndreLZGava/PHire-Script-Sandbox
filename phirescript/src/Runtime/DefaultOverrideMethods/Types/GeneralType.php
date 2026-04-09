<?php

declare(strict_types=1);

namespace PHireScript\Runtime\DefaultOverrideMethods\Types;

use PHireScript\Runtime\DefaultOverrideMethods\BaseMethods;
use PHireScript\Runtime\DefaultOverrideMethods\BaseParams;
use PHireScript\Runtime\DefaultOverrideMethods\BaseRegistryFunctions;

class GeneralType
{
    public function destroy()
    {
        return new BaseMethods(
            name: 'destroy!',
            phpCodeForConversion: '\unset(@self)',
            returnOfPhpExecution: [],
        );
    }

    public function defined()
    {
        return new BaseMethods(
            name: 'defined?',
            phpCodeForConversion: 'isset(@self)',
            returnOfPhpExecution: ['Bool'],
        );
    }

    public function getClass()
    {
        return new BaseMethods(
            name: 'getClass',
            phpCodeForConversion: '\is_object(@self) ? \get_class(@self) : \gettype(@self)',
            returnOfPhpExecution: ['String'],
        );
    }

    public function show()
    {
        return new BaseMethods(
            name: 'show!',
            phpCodeForConversion: 'if(\is_array(@self) || \is_object(@self)) {\print_r(@self);} else {echo @self ;}',
            returnOfPhpExecution: [],
        );
    }

    public function display()
    {
        return new BaseMethods(
            name: 'display!',
            phpCodeForConversion: '\print_r(@self)',
            returnOfPhpExecution: [],
        );
    }

    /**
     * @todo not working yet, but I believe we should be able to persist
     * function as a param of base method and being able to call it in runtime
     *
     * @return boolean
     */
    public function is()
    {
        $isOfType = function (mixed $value, string $type): bool {
            if (is_object($value) && (class_exists($type) || interface_exists($type))) {
                return $value instanceof $type;
            }

            return match (strtolower($type)) {
                'string'  => \is_string($value),
                'int', 'integer' => \is_int($value),
                'float', 'double' => is_float($value),
                'bool', 'boolean' => is_bool($value),
                'array'   => is_array($value),
                'null'    => is_null($value),
                'object'  => is_object($value),
                'callable' => is_callable($value),
                'resource' => is_resource($value),
                default   => false,
            };
        };

        return new BaseMethods(
            name: 'is?',
            phpCodeForConversion: "is(@self, @type)",
            returnOfPhpExecution: ['Bool'],
            subTypes: [],
            params: [
                new BaseParams(name: '@type', type: 'mixed', required: true)
            ],
            function: new BaseRegistryFunctions(className: 'GeneralValidator', name: 'is', function: $isOfType),
        );
    }
}
