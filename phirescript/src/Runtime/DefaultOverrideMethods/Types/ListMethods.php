<?php

declare(strict_types=1);

namespace PHireScript\Runtime\DefaultOverrideMethods\Types;

use PHireScript\Runtime\DefaultOverrideMethods\BaseMethods;
use PHireScript\Runtime\DefaultOverrideMethods\BaseParams;

class ListMethods extends GeneralType
{
    public function __construct(
        public array $types,
    ) {
    }

    public function contains()
    {
        return new BaseMethods(
            name: 'contains?',
            phpCodeForConversion: '\in_array(@searching, @self, true)',
            returnOfPhpExecution: ['Bool'],
            subTypes: $this->types,
            params: [
                new BaseParams(name: '@searching', type: \implode('|', $this->types), required: true)
            ]
        );
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
                'unset(@self[@index]);',
                '@self = \array_values(@self);',
                'return @self;'
            ],
            returnOfPhpExecution: ['List'],
            subTypes: $this->types,
            params: [
                new BaseParams(name: '@index', type: 'int', required: true)
            ]
        );
    }

    public function join()
    {
        return new BaseMethods(
            name: 'join',
            phpCodeForConversion: '\implode(@separator, @self)',
            returnOfPhpExecution: ['String'],
            subTypes: $this->types,
            params: [
                new BaseParams(name: '@separator', type: 'string', required: true)
            ]
        );
    }

    public function reverse()
    {
        return new BaseMethods(
            name: 'reverse',
            phpCodeForConversion: '\array_reverse(@self)',
            returnOfPhpExecution: ['List'],
            subTypes: $this->types,
        );
    }

    public function sort()
    {
        return new BaseMethods(
            name: 'sort',
            phpCodeForConversion: [
                '$__tmp = @self;',
                '\sort($__tmp);',
                'return $__tmp;'
            ],
            returnOfPhpExecution: ['List'],
            subTypes: $this->types,
        );
    }

    public function clear()
    {
        return new BaseMethods(
            name: 'clear',
            phpCodeForConversion: '@self = []',
            returnOfPhpExecution: ['List'],
            subTypes: $this->types,
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
                new BaseParams(name: '@searching', type: 'int', required: true)
            ]
        );
    }

    public function set()
    {
        return new BaseMethods(
            name: 'set',
            phpCodeForConversion: '@self[@key] = @value',
            returnOfPhpExecution: ['List'],
            subTypes: $this->types,
            params: [
                new BaseParams(name: '@value', type: \implode('|', $this->types), required: true),
                new BaseParams(name: '@key', type: 'int', required: true, relatedKeyParam: true),
            ]
        );
    }

    public function append()
    {
        return new BaseMethods(
            name: 'append',
            phpCodeForConversion: '@self[] = @value',
            returnOfPhpExecution: ['List'],
            subTypes: $this->types,
            params: [
                new BaseParams(name: '@value', type: \implode('|', $this->types), required: true),
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
            phpCodeForConversion: ['return \count(@self) === 0 ? null : \current(@self)'],
            returnOfPhpExecution: ['Null', ...$this->types],
            subTypes: $this->types,
            params: [],
        );
    }
}
