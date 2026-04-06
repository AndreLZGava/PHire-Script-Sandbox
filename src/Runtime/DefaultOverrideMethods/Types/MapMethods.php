<?php

declare(strict_types=1);

namespace PHireScript\Runtime\DefaultOverrideMethods\Types;

use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\DefaultOverrideMethods\BaseMethods;
use PHireScript\Runtime\DefaultOverrideMethods\BaseParams;

class MapMethods extends GeneralType
{
    public function __construct(
        public array $types = [],
    ) {
    }

    public function length()
    {
        return new BaseMethods(
            name: 'length',
            phpCodeForConversion: '\count(@self)',
            returnOfPhpExecution: ['Int'],
            subTypes: $this->types,
        );
    }

    public function remove()
    {
        return new BaseMethods(
            name: 'remove',
            phpCodeForConversion: [
                '$__tmp = @self;',
                'unset($__tmp[@key]);',
                'return $__tmp;'
            ],
            returnOfPhpExecution: ['Map'],
            subTypes: $this->types,
            params: [
                new BaseParams(name: '@index', type: 'string', required: true)
            ]
        );
    }

    public function reverse()
    {
        return new BaseMethods(
            name: 'reverse',
            phpCodeForConversion: '\array_reverse(@self, true)',
            returnOfPhpExecution: ['Map'],
            subTypes: $this->types,
        );
    }

    public function sort()
    {
        return new BaseMethods(
            name: 'sort',
            phpCodeForConversion: [
                '$__tmp = @self;',
                '\asort($__tmp);',
                'return $__tmp;'
            ],
            returnOfPhpExecution: ['Map'],
            subTypes: $this->types,
        );
    }

    public function clear()
    {
        return new BaseMethods(
            name: 'clear',
            phpCodeForConversion: '@self = []',
            returnOfPhpExecution: ['Map'],
            subTypes: $this->types,
        );
    }

    public function containsValue()
    {
        return new BaseMethods(
            name: 'containsValue?',
            phpCodeForConversion: '\in_array(@searching, @self, true)',
            returnOfPhpExecution: ['Bool'],
            subTypes: $this->types,
            params: [
                new BaseParams(name: '@searching', type: 'mixed', required: true)
            ]
        );
    }

    public function hasKey()
    {
        return new BaseMethods(
            name: 'hasKey?',
            phpCodeForConversion: '\array_key_exists(@searching, @self)',
            returnOfPhpExecution: ['Bool'],
            subTypes: $this->types,
            params: [
                new BaseParams(name: '@searching', type: 'string', required: true)
            ]
        );
    }

    public function append()
    {
        return new BaseMethods(
            name: 'append',
            phpCodeForConversion: [
                '$__tmp = @self;',
                '$__tmp[@key] = @value;',
                'return $__tmp;'
            ],
            returnOfPhpExecution: ['Map'],
            subTypes: $this->types,
            params: [
                new BaseParams(name: '@value', type: \implode('|', $this->types), required: true),
                new BaseParams(name: '@key', type: 'string', required: true, relatedKeyParam: true),
            ]
        );
    }

    public function last()
    {
        return new BaseMethods(
            name: 'last',
            phpCodeForConversion: ['return \count(@self) === 0 ? null : @self[\array_key_last(@self)]'],
            returnOfPhpExecution: ['Null', ...$this->types],
            subTypes: $this->types,
            params: [],
        );
    }

    public function first()
    {
        return new BaseMethods(
            name: 'first',
            phpCodeForConversion: ['return empty(@self) ? null : @self[\array_key_first(@self)]'],
            returnOfPhpExecution: ['Null', ...$this->types],
            subTypes: $this->types,
            params: [],
        );
    }

    public function keys()
    {
        return new BaseMethods(
            name: 'keys',
            phpCodeForConversion: '\array_keys(@self)',
            returnOfPhpExecution: ['List'],
            subTypes: ['String'],
            params: [],
        );
    }

    public function values()
    {
        return new BaseMethods(
            name: 'values',
            phpCodeForConversion: '\array_values(@self)',
            returnOfPhpExecution: ['List'],
            subTypes: $this->types,
            params: [],
        );
    }
}
