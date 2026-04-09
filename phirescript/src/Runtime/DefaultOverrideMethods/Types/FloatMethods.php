<?php

declare(strict_types=1);

namespace PHireScript\Runtime\DefaultOverrideMethods\Types;

use PHireScript\Runtime\DefaultOverrideMethods\BaseMethods;
use PHireScript\Runtime\DefaultOverrideMethods\BaseParams;
use PHireScript\Runtime\DefaultOverrideMethods\BaseRegistryFunctions;

class FloatMethods extends GeneralType {
  public function add() {
    return new BaseMethods(
      name: 'add',
      phpCodeForConversion: '@self + @value',
      returnOfPhpExecution: ['Float'],
      params: [
        new BaseParams('@value', 'float', true)
      ]
    );
  }

  public function subtract() {
    return new BaseMethods(
      name: 'subtract',
      phpCodeForConversion: '@self - @value',
      returnOfPhpExecution: ['Float'],
      params: [
        new BaseParams('@value', 'float', true)
      ]
    );
  }

  public function multipliedBy() {
    return new BaseMethods(
      name: 'multipliedBy',
      phpCodeForConversion: '@self * @value',
      returnOfPhpExecution: ['Float'],
      params: [
        new BaseParams('@value', 'float', true)
      ]
    );
  }

  public function dividedBy() {
    return new BaseMethods(
      name: 'dividedBy',
      phpCodeForConversion: '@value == 0 ? null : @self / @value',
      returnOfPhpExecution: ['Float'],
      params: [
        new BaseParams('@value', 'float', true)
      ]
    );
  }

  public function abs() {
    return new BaseMethods(
      name: 'abs',
      phpCodeForConversion: '\abs(@self)',
      returnOfPhpExecution: ['Float']
    );
  }

  public function floor() {
    return new BaseMethods(
      name: 'floor',
      phpCodeForConversion: '\floor(@self)',
      returnOfPhpExecution: ['Int']
    );
  }

  public function ceil() {
    return new BaseMethods(
      name: 'ceil',
      phpCodeForConversion: '\ceil(@self)',
      returnOfPhpExecution: ['Int']
    );
  }

  public function round() {
    return new BaseMethods(
      name: 'round',
      phpCodeForConversion: '\round(@self, @precision)',
      returnOfPhpExecution: ['Float'],
      params: [
        new BaseParams('@precision', 'int', false)
      ]
    );
  }

  public function sqrt() {
    return new BaseMethods(
      name: 'sqrt',
      phpCodeForConversion: '\sqrt(@self)',
      returnOfPhpExecution: ['Float']
    );
  }

  public function powerOf() {
    return new BaseMethods(
      name: 'powerOf',
      phpCodeForConversion: '\pow(@self, @exp)',
      returnOfPhpExecution: ['Float'],
      params: [
        new BaseParams('@exp', 'float', true)
      ]
    );
  }

  public function clamp() {
    return new BaseMethods(
      name: 'clamp',
      phpCodeForConversion: '\max(@min, \min(@self, @max))',
      returnOfPhpExecution: ['Float'],
      params: [
        new BaseParams('@min', 'float', true),
        new BaseParams('@max', 'float', true),
      ]
    );
  }

  public function between() {
    return new BaseMethods(
      name: 'between?',
      phpCodeForConversion: '@self >= @min && @self <= @max',
      returnOfPhpExecution: ['Bool'],
      params: [
        new BaseParams('@min', 'float', true),
        new BaseParams('@max', 'float', true),
      ]
    );
  }

  public function toInt() {
    return new BaseMethods(
      name: 'toInt',
      phpCodeForConversion: '(int) @self',
      returnOfPhpExecution: ['Int']
    );
  }

  public function toString() {
    return new BaseMethods(
      name: 'toString',
      phpCodeForConversion: '(string) @self',
      returnOfPhpExecution: ['String']
    );
  }

  public function isInteger() {
    return new BaseMethods(
      name: 'isInteger?',
      phpCodeForConversion: 'floor(@self) == @self',
      returnOfPhpExecution: ['Bool']
    );
  }

  public function format() {
    return new BaseMethods(
      name: 'format',
      phpCodeForConversion: '\number_format(@self, @decimals, ".", "")',
      returnOfPhpExecution: ['String'],
      params: [
        new BaseParams('@decimals', 'int', true)
      ]
    );
  }

  public function to() {
    return new BaseMethods(
      name: 'to',
      phpCodeForConversion: '
            $result = [];
            $start = @self;
            $end = @end;
            $step = @step ?? 1.0;

            if ($step != 0.0) {
                if ($start < $end) {
                    for ($i = $start; $i <= $end; $i += $step) {
                        $result[] = round($i, 10);
                    }
                } else {
                    for ($i = $start; $i >= $end; $i -= abs($step)) {
                        $result[] = round($i, 10);
                    }
                }
            }

            return $result;
        ',
      returnOfPhpExecution: ['Array<Float>'],
      params: [
        new BaseParams('@end', 'float', true),
        new BaseParams('@step', 'float', false)
      ]
    );
  }

  public function modBy() {

    return new BaseMethods(
      name: 'modBy',
      phpCodeForConversion: 'fmod(@self, @mod)',
      returnOfPhpExecution: ['Float'],
      params: [
        new BaseParams('@mod', 'float', true)
      ],
    );
  }
}
