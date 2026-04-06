<?php

declare(strict_types=1);

namespace PHireScript\Runtime\DefaultOverrideMethods\Types;

use PHireScript\Runtime\DefaultOverrideMethods\BaseMethods;
use PHireScript\Runtime\DefaultOverrideMethods\BaseParams;

class IntMethods extends GeneralType {

  public function add() {
    return new BaseMethods(
      'add',
      phpCodeForConversion: '@self + @value',
      returnOfPhpExecution: ['Int', 'Float'],
      params: [
        new BaseParams('@value', 'int|float', true),
      ]
    );
  }

  public function subtract() {
    return new BaseMethods(
      'subtract',
      phpCodeForConversion: '@self - @value',
      returnOfPhpExecution: ['Int', 'Float'],
      params: [
        new BaseParams('@value', 'int|float', true),
      ]
    );
  }

  public function multiplyBy() {
    return new BaseMethods(
      'multiplyBy',
      phpCodeForConversion: '@self * @value',
      returnOfPhpExecution: ['Int', 'Float'],
      params: [
        new BaseParams('@value', 'int|float', true),
      ]
    );
  }

  public function divideBy() {
    return new BaseMethods(
      'divideBy',
      phpCodeForConversion: '@value == 0 ? null : @self / @value',
      returnOfPhpExecution: ['Float', 'Null'],
      params: [
        new BaseParams('@value', 'int|float', true),
      ]
    );
  }

  public function modBy() {
    return new BaseMethods(
      'modBy',
      phpCodeForConversion: '@self % @value',
      returnOfPhpExecution: ['Int'],
      params: [
        new BaseParams('@value', 'int', true),
      ]
    );
  }

  public function powerOf() {
    return new BaseMethods(
      'powerOf',
      phpCodeForConversion: '@self ** @value',
      returnOfPhpExecution: ['Int', 'Float'],
      params: [
        new BaseParams('@value', 'int|float', true),
      ]
    );
  }

  public function increment() {
    return new BaseMethods(
      'increment',
      phpCodeForConversion: '@self + 1',
      returnOfPhpExecution: ['Int'],
    );
  }

  public function decrement() {
    return new BaseMethods(
      'decrement',
      phpCodeForConversion: '@self - 1',
      returnOfPhpExecution: ['Int'],
    );
  }

  public function greaterThan() {
    return new BaseMethods(
      'greaterThan?',
      '@self > @value',
      ['Bool'],
      params: [
        new BaseParams('@value', 'int|float', true),
      ]
    );
  }

  public function lessThan() {
    return new BaseMethods(
      'lessThan?',
      '@self < @value',
      ['Bool'],
      params: [
        new BaseParams('@value', 'int|float', true),
      ]
    );
  }

  public function equals() {
    return new BaseMethods(
      'equals?',
      '@self === @value',
      ['Bool'],
      params: [
        new BaseParams('@value', 'mixed', true),
      ]
    );
  }

  public function abs() {
    return new BaseMethods(
      'abs',
      '\abs(@self)',
      ['Int']
    );
  }

  public function sqrt() {
    return new BaseMethods(
      'sqrt',
      '\sqrt(@self)',
      ['Float']
    );
  }

  public function isEven() {
    return new BaseMethods(
      'isEven?',
      '@self % 2 === 0',
      ['Bool']
    );
  }

  public function isOdd() {
    return new BaseMethods(
      'isOdd?',
      '@self % 2 !== 0',
      ['Bool']
    );
  }

  public function isPositive() {
    return new BaseMethods(
      'isPositive?',
      '@self > 0',
      ['Bool']
    );
  }

  public function isNegative() {
    return new BaseMethods(
      'isNegative?',
      '@self < 0',
      ['Bool']
    );
  }

  public function clamp() {
    return new BaseMethods(
      'clamp',
      'max(@min, min(@self, @max))',
      ['Int'],
      params: [
        new BaseParams('@min', 'int', true),
        new BaseParams('@max', 'int', true),
      ]
    );
  }

  public function toString() {
    return new BaseMethods(
      'toString',
      '(string) @self',
      ['String']
    );
  }

  public function toFloat() {
    return new BaseMethods(
      'toFloat',
      '(float) @self',
      ['Float']
    );
  }

  public function to() {
    return new BaseMethods(
      'to',
      phpCodeForConversion: [
        '$__result = [];',

        'if (@self <= @to) {',
        'for ($__i = @self; $__i <= @to; $__i++) {',
        '$__result[] = $__i;',
        '}',
        '} else {',
        'for ($__i = @self; $__i >= @to; $__i--) {',
        '$__result[] = $__i;',
        '}',
        '}',

        'return $__result;'
      ],
      returnOfPhpExecution: ['Array'],
      subTypes: ['Int'],
      params: [
        new BaseParams('@to', 'int', true),
      ]
    );
  }

  public function times() {
    return new BaseMethods(
      name: 'times',
      phpCodeForConversion: '
            $__callback = @callback;
            for ($__i = 0; $__i < @self; $__i++) {
                $__callback($__i);
            }
            return null;
        ',
      returnOfPhpExecution: ['Null'],
      params: [
        new BaseParams(
          name: '@callback',
          type: 'callable',
          required: true
        )
      ]
    );
  }

  public function upTo() {
    return new BaseMethods(
      'upTo',
      phpCodeForConversion: [
        '$__result = [];',
        'for ($__i = @self; $__i <= @to; $__i++) {',
        '$__result[] = $__i;',
        '}',
        'return $__result;'
      ],
      returnOfPhpExecution: ['Array'],
      subTypes: ['Int'],
      params: [
        new BaseParams('@to', 'int', true),
      ]
    );
  }

  public function downTo() {
    return new BaseMethods(
      'downTo',
      phpCodeForConversion: [
        '$__result = [];',
        'for ($__i = @self; $__i >= @to; $__i--) {',
        '$__result[] = $__i;',
        '}',
        'return $__result;'
      ],
      returnOfPhpExecution: ['Array'],
      subTypes: ['Int'],
      params: [
        new BaseParams('@to', 'int', true),
      ]
    );
  }
}
