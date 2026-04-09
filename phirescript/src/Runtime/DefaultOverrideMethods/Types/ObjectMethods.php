<?php

declare(strict_types=1);

namespace PHireScript\Runtime\DefaultOverrideMethods\Types;

use PHireScript\Runtime\DefaultOverrideMethods\BaseMethods;
use PHireScript\Runtime\DefaultOverrideMethods\BaseParams;

class ObjectMethods extends GeneralType
{
    public function __construct()
    {
    }

    public function hasProperty()
    {
        return new BaseMethods(
            'hasProperty?',
            phpCodeForConversion: '\property_exists(@self, @property)',
            returnOfPhpExecution: ['Bool'],
            subTypes: ['String', 'Bool', 'Array', 'Number'],
            params: [
                new BaseParams(name: '@property', type: 'string', required: true)
            ]
        );
    }

    public function clone()
    {
        return new BaseMethods(
            'clone',
            phpCodeForConversion: 'clone @self',
            returnOfPhpExecution: ['Object'],
            subTypes: [],
            params: []
        );
    }

    public function getProperties()
    {
        return new BaseMethods(
            'getProperties',
            phpCodeForConversion: '\get_object_vars(@self)',
            returnOfPhpExecution: ['Array'],
            subTypes: [],
            params: []
        );
    }

    public function hasMethod()
    {
        return new BaseMethods(
            name: 'hasMethod?',
            phpCodeForConversion: '\method_exists(@self, @method)',
            returnOfPhpExecution: ['Bool'],
            params: [
                new BaseParams('@method', 'string', true)
            ]
        );
    }

    public function get()
    {
        return new BaseMethods(
            name: 'get',
            phpCodeForConversion: '@self->@property ?? @default',
            returnOfPhpExecution: ['Mixed'],
            params: [
                new BaseParams('@property', 'string', true),
                new BaseParams('@default', 'mixed', false)
            ]
        );
    }

    public function set()
    {
        return new BaseMethods(
            name: 'set',
            phpCodeForConversion: '@self->@property = @value',
            returnOfPhpExecution: ['Object'],
            params: [
                new BaseParams('@property', 'string', true),
                new BaseParams('@value', 'mixed', true)
            ]
        );
    }

    public function merge()
    {
        return new BaseMethods(
            name: 'merge',
            phpCodeForConversion: '(object) array_merge((array) @self, (array) @object)',
            returnOfPhpExecution: ['Object'],
            params: [
                new BaseParams('@object', 'object', true)
            ]
        );
    }

    public function remove()
    {
        return new BaseMethods(
            name: 'remove',
            phpCodeForConversion: 'unset(@self->@property)',
            returnOfPhpExecution: ['Object'],
            params: [
                new BaseParams('@property', 'string', true)
            ]
        );
    }

    public function toArray()
    {
        return new BaseMethods(
            name: 'toArray',
            phpCodeForConversion: '(array) @self',
            returnOfPhpExecution: ['Array']
        );
    }

    public function toJson()
    {
        return new BaseMethods(
            name: 'toJson',
            phpCodeForConversion: '\json_encode(@self)',
            returnOfPhpExecution: ['String']
        );
    }

    public function keys()
    {
        return new BaseMethods(
            name: 'keys',
            phpCodeForConversion: '\array_keys(\get_object_vars(@self))',
            returnOfPhpExecution: ['Array']
        );
    }

    public function values()
    {
        return new BaseMethods(
            name: 'values',
            phpCodeForConversion: '\array_values(\get_object_vars(@self))',
            returnOfPhpExecution: ['Array']
        );
    }

    public function entries()
    {
        return new BaseMethods(
            name: 'entries',
            phpCodeForConversion: '\get_object_vars(@self)',
            returnOfPhpExecution: ['Array']
        );
    }

    public function each()
    {
        return new BaseMethods(
            name: 'each',
            phpCodeForConversion: 'foreach(\get_object_vars(@self) as $k => $v) { @callback($v, $k); }',
            returnOfPhpExecution: [],
            params: [
                new BaseParams('@callback', 'callable', true)
            ]
        );
    }

    public function map()
    {
        return new BaseMethods(
            name: 'map',
            phpCodeForConversion: '(object) array_map(@callback, \get_object_vars(@self))',
            returnOfPhpExecution: ['Object'],
            params: [
                new BaseParams('@callback', 'callable', true)
            ]
        );
    }

    public function filter()
    {
        return new BaseMethods(
            name: 'filter',
            phpCodeForConversion: '(object) array_filter(\get_object_vars(@self), @callback)',
            returnOfPhpExecution: ['Object'],
            params: [
                new BaseParams('@callback', 'callable', true)
            ]
        );
    }

    public function isEmpty()
    {
        return new BaseMethods(
            name: 'isEmpty?',
            phpCodeForConversion: 'empty(\get_object_vars(@self))',
            returnOfPhpExecution: ['Bool']
        );
    }

    public function count()
    {
        return new BaseMethods(
            name: 'count',
            phpCodeForConversion: '\count(\get_object_vars(@self))',
            returnOfPhpExecution: ['Int']
        );
    }
}
