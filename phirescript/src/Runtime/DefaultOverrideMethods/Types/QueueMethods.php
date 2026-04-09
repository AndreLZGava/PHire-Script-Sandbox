<?php

declare(strict_types=1);

namespace PHireScript\Runtime\DefaultOverrideMethods\Types;

use PHireScript\Runtime\DefaultOverrideMethods\BaseMethods;
use PHireScript\Runtime\DefaultOverrideMethods\BaseParams;

class QueueMethods extends GeneralType {
    public function __construct(
        public array $types,
    ) {
    }

    public function enqueue() {
        return new BaseMethods(
            'enqueue',
            phpCodeForConversion: ['\array_push(@self, @params)', 'return @self'],
            returnOfPhpExecution: ['Queue'],
            subTypes: $this->types,
            params: [
                new BaseParams('@params', 'array', true),
            ],
        );
    }

    public function dequeue() {
        return new BaseMethods(
            'dequeue',
            phpCodeForConversion: '\array_shift(@self)',
            returnOfPhpExecution: ['Null', ...$this->types],
            subTypes: $this->types,
        );
    }

    public function peek() {
        return new BaseMethods(
            'peek',
            phpCodeForConversion: '@self[0] ?? null',
            returnOfPhpExecution: ['Null', ...$this->types],
        );
    }

    public function contains() {
        return new BaseMethods(
            name: 'contains?',
            phpCodeForConversion: '\in_array(@searching, @self, true)',
            returnOfPhpExecution: ['Bool'],
            subTypes: $this->types,
            params: [
                new BaseParams(name: '@searching', type: 'mixed', required: true)
            ]
        );
    }

    public function length() {
        return new BaseMethods(
            name: 'length',
            phpCodeForConversion: '\count(@self)',
            returnOfPhpExecution: ['Int'],
            subTypes: $this->types,
        );
    }

    public function clear() {
        return new BaseMethods(
            name: 'clear',
            phpCodeForConversion: '@self = []',
            returnOfPhpExecution: ['Queue'],
            subTypes: $this->types,
        );
    }
}
