<?php

declare(strict_types=1);

namespace PHireScript\Runtime\DefaultOverrideMethods\Types;

use PHireScript\Runtime\DefaultOverrideMethods\BaseMethods;
use PHireScript\Runtime\DefaultOverrideMethods\BaseParams;

class BoolMethods extends GeneralType {
  public function not() {
    return new BaseMethods(
      name: 'not',
      phpCodeForConversion: '!@self',
      returnOfPhpExecution: ['Bool']
    );
  }

  public function and() {
    return new BaseMethods(
      name: 'and',
      phpCodeForConversion: '@self && @value',
      returnOfPhpExecution: ['Bool'],
      params: [
        new BaseParams('@value', 'bool', true)
      ]
    );
  }

  public function or() {
    return new BaseMethods(
      name: 'or',
      phpCodeForConversion: '@self || @value',
      returnOfPhpExecution: ['Bool'],
      params: [
        new BaseParams('@value', 'bool', true)
      ]
    );
  }

  public function xor() {
    return new BaseMethods(
      name: 'xor',
      phpCodeForConversion: '@self xor @value',
      returnOfPhpExecution: ['Bool'],
      params: [
        new BaseParams('@value', 'bool', true)
      ]
    );
  }

  public function equals() {
    return new BaseMethods(
      name: 'equals?',
      phpCodeForConversion: '@self === @value',
      returnOfPhpExecution: ['Bool'],
      params: [
        new BaseParams('@value', 'bool', true)
      ]
    );
  }

  public function toInt() {
    return new BaseMethods(
      name: 'toInt',
      phpCodeForConversion: '@self ? 1 : 0',
      returnOfPhpExecution: ['Int']
    );
  }

  public function toString() {
    return new BaseMethods(
      name: 'toString',
      phpCodeForConversion: '@self ? "true" : "false"',
      returnOfPhpExecution: ['String']
    );
  }

  public function toggle() {
    return new BaseMethods(
      name: 'toggle',
      phpCodeForConversion: '!@self',
      returnOfPhpExecution: ['Bool']
    );
  }

  public function ifTrue() {
    return new BaseMethods(
      name: 'ifTrue',
      phpCodeForConversion: '@self ? @value : null',
      returnOfPhpExecution: ['Mixed'],
      params: [
        new BaseParams('@value', 'mixed', true)
      ]
    );
  }

  public function ifFalse() {
    return new BaseMethods(
      name: 'ifFalse',
      phpCodeForConversion: '!@self ? @value : null',
      returnOfPhpExecution: ['Mixed'],
      params: [
        new BaseParams('@value', 'mixed', true)
      ]
    );
  }

  public function then() {
    return new BaseMethods(
      name: 'then',
      phpCodeForConversion: '@self ? @value : null',
      returnOfPhpExecution: ['Mixed'],
      params: [
        new BaseParams('@value', 'mixed', true)
      ]
    );
  }

  public function else() {
    return new BaseMethods(
      name: 'else',
      phpCodeForConversion: '!@self ? @value : null',
      returnOfPhpExecution: ['Mixed'],
      params: [
        new BaseParams('@value', 'mixed', true)
      ]
    );
  }

  public function map() {
    return new BaseMethods(
      name: 'map',
      phpCodeForConversion: '@self ? @trueValue : @falseValue',
      returnOfPhpExecution: ['Mixed'],
      params: [
        new BaseParams('@trueValue', 'mixed', true),
        new BaseParams('@falseValue', 'mixed', true),
      ]
    );
  }

  public function when() {
    return new BaseMethods(
      name: 'when',
      phpCodeForConversion: '@self ? @callback() : null',
      returnOfPhpExecution: ['Mixed'],
      params: [
        new BaseParams('@callback', 'callable', true)
      ]
    );
  }

  public function unless() {
    return new BaseMethods(
      name: 'unless',
      phpCodeForConversion: '!@self ? @callback() : null',
      returnOfPhpExecution: ['Mixed'],
      params: [
        new BaseParams('@callback', 'callable', true)
      ]
    );
  }
}
