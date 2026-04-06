<?php

declare(strict_types=1);

namespace PHireScript\Runtime\DefaultOverrideMethods\Types;

use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\DefaultOverrideMethods\BaseMethods;
use PHireScript\Runtime\DefaultOverrideMethods\BaseParams;

class ArrayMethods extends GeneralType
{
    public function __construct(
        public array $types = [],
    ) {
    }

    public function contains()
    {
        return new BaseMethods(
            name: 'contains?',
            phpCodeForConversion: '\in_array(@searching, @self)',
            returnOfPhpExecution: ['Bool'],
            subTypes: $this->types,
            params: [
                new BaseParams(name: '@searching', type: 'mixed', required: true)
            ]
        );
    }

    public function add()
    {
        return new BaseMethods(
            name: 'add',
            phpCodeForConversion: '@self[@key] = @value',
            returnOfPhpExecution: ['Array'],
            subTypes: [],
            params: [
                new BaseParams(name: '@value', type: 'mixed', required: true),
                new BaseParams(
                    name: '@key',
                    type: 'string',
                    required: false,
                    defaultValue: null,
                    relatedKeyParam: true
                ),
            ],
            overridesSelfParam: false,
        );
    }

    public function addEnd()
    {
        return new BaseMethods(
            name: 'addEnd!',
            phpCodeForConversion: '\array_push(@self, @params)',
            returnOfPhpExecution: [],
            subTypes: $this->types,
            params: [
                new BaseParams('@params', 'array', true),
            ],
        );
    }

    public function addStart()
    {
        return new BaseMethods(
            name: 'addStart!',
            phpCodeForConversion: '\array_unshift(@self, @params)',
            returnOfPhpExecution: [],
            subTypes: $this->types,
            params: [
                new BaseParams('@params', 'array', true),
            ],
        );
    }

    public function last()
    {
        return new BaseMethods(
            name: 'last',
            phpCodeForConversion: 'empty(@self) ? null : @self[\array_key_last(@self)];',
            returnOfPhpExecution: ['Mixed'],
            subTypes: $this->types,
            params: [],
        );
    }

    public function first()
    {
        return new BaseMethods(
            name: 'first',
            phpCodeForConversion: '\current(@self ?? [])',
            returnOfPhpExecution: ['Mixed'],
            subTypes: $this->types,
            params: [],
        );
    }

    public function remove()
    {
        return new BaseMethods(
            name: 'remove',
            phpCodeForConversion: 'unset(@self[@key])',
            returnOfPhpExecution: ['Array'],
            params: [
                new BaseParams('@key', 'mixed', true)
            ]
        );
    }

    public function removeValue()
    {
        return new BaseMethods(
            name: 'removeValue',
            phpCodeForConversion: '@self = array_filter(@self, fn($v) => $v !== @value)',
            returnOfPhpExecution: ['Array'],
            params: [
                new BaseParams('@value', 'mixed', true)
            ]
        );
    }

    public function length()
    {
        return new BaseMethods(
            name: 'length',
            phpCodeForConversion: '\count(@self)',
            returnOfPhpExecution: ['Int']
        );
    }

    public function isEmpty()
    {
        return new BaseMethods(
            name: 'isEmpty?',
            phpCodeForConversion: 'empty(@self)',
            returnOfPhpExecution: ['Bool']
        );
    }

    public function map()
    {
        return new BaseMethods(
            name: 'map',
            phpCodeForConversion: '\array_map(@callback, @self)',
            returnOfPhpExecution: ['Array'],
            params: [
                new BaseParams('@callback', 'callable', true)
            ]
        );
    }

    public function filter()
    {
        return new BaseMethods(
            name: 'filter',
            phpCodeForConversion: '\array_filter(@self, @callback)',
            returnOfPhpExecution: ['Array'],
            params: [
                new BaseParams('@callback', 'callable', true)
            ]
        );
    }

    public function reduce()
    {
        return new BaseMethods(
            name: 'reduce',
            phpCodeForConversion: '\array_reduce(@self, @callback, @initial)',
            returnOfPhpExecution: ['Mixed'],
            params: [
                new BaseParams('@callback', 'callable', true),
                new BaseParams('@initial', 'mixed', false)
            ]
        );
    }

    public function find()
    {
        return new BaseMethods(
            name: 'find',
            phpCodeForConversion: '\current(array_filter(@self, @callback))',
            returnOfPhpExecution: ['Mixed'],
            params: [
                new BaseParams('@callback', 'callable', true)
            ]
        );
    }

    public function findIndex()
    {
        return new BaseMethods(
            name: 'findIndex',
            phpCodeForConversion: 'array_search(true, array_map(@callback, @self))',
            returnOfPhpExecution: ['Int'],
            params: [
                new BaseParams('@callback', 'callable', true)
            ]
        );
    }

    public function sort()
    {
        return new BaseMethods(
            name: 'sort!',
            phpCodeForConversion: '\sort(@self)',
            returnOfPhpExecution: ['Void']
        );
    }

    public function reverse()
    {
        return new BaseMethods(
            name: 'reverse',
            phpCodeForConversion: '\array_reverse(@self)',
            returnOfPhpExecution: ['Array']
        );
    }

    public function merge()
    {
        return new BaseMethods(
            name: 'merge',
            phpCodeForConversion: '\array_merge(@self, @array)',
            returnOfPhpExecution: ['Array'],
            params: [
                new BaseParams('@array', 'array', true)
            ]
        );
    }

    public function unique()
    {
        return new BaseMethods(
            name: 'unique',
            phpCodeForConversion: '\array_unique(@self)',
            returnOfPhpExecution: ['Array']
        );
    }

    public function get()
    {
        return new BaseMethods(
            name: 'get',
            phpCodeForConversion: '@self[@key] ?? @default',
            returnOfPhpExecution: ['Mixed'],
            params: [
                new BaseParams('@key', 'mixed', true),
                new BaseParams('@default', 'mixed', false)
            ]
        );
    }

    public function each()
    {
        return new BaseMethods(
            name: 'each',
            phpCodeForConversion: 'foreach(@self as $k => $v) { @callback($v, $k); }',
            returnOfPhpExecution: [],
            params: [
                new BaseParams('@callback', 'callable', true)
            ]
        );
    }
}
