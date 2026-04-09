<?php

declare(strict_types=1);

namespace PHireScript\Runtime\DefaultOverrideMethods\Types;

use PHireScript\Runtime\DefaultOverrideMethods\BaseMethods;
use PHireScript\Runtime\DefaultOverrideMethods\BaseParams;

class UrlMethods extends GeneralType
{
    public function encode()
    {
        return new BaseMethods(
            name: 'encode',
            phpCodeForConversion: 'rawurlencode(@self)',
            returnOfPhpExecution: ['Url'],
            subTypes: [],
            params: [],
        );
    }

    public function decode()
    {
        return new BaseMethods(
            name: 'decode',
            phpCodeForConversion: 'rawurldecode(@self)',
            returnOfPhpExecution: ['Url'],
            subTypes: [],
            params: [],
        );
    }

    public function parse()
    {
        return new BaseMethods(
            name: 'parse',
            phpCodeForConversion: '(object) parse_url(@self)',
            returnOfPhpExecution: ['Object'],
            subTypes: [],
            params: [],
        );
    }

    public function getQuery()
    {
        return new BaseMethods(
            name: 'parse',
            phpCodeForConversion: '(object) parse_str(@self)',
            returnOfPhpExecution: ['Object'],
            subTypes: [],
            params: [],
        );
    }

  /**
   * Isso pode dar problema a entrada de self deveria ser um array
   *
   * @return void
   */
    public function setQuery()
    {
        return new BaseMethods(
            name: 'parse',
            phpCodeForConversion: 'parse_str((array) @self)',
            returnOfPhpExecution: ['Url'],
            subTypes: [],
            params: [],
        );
    }
}
