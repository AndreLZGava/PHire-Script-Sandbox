<?php

declare(strict_types=1);

namespace PHireScript\Runtime\DefaultOverrideMethods\Types;

use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\DefaultOverrideMethods\BaseMethods;
use PHireScript\Runtime\DefaultOverrideMethods\BaseParams;

class StringMethods extends GeneralType
{
    public function length()
    {
        return new BaseMethods(
            'length',
            '\strlen(@self)',
            ['Int'],
        );
    }

    public function toUpperCase()
    {
        return new BaseMethods(
            'toUpperCase',
            phpCodeForConversion: '\mb_strtoupper(@self, @format)',
            returnOfPhpExecution: ['String'],
            params: [
                new BaseParams('@format', 'string', false, 'UTF-8'),
            ],
        );
    }

    public function toLowerCase()
    {
        return new BaseMethods(
            'toLowerCase',
            phpCodeForConversion: '\mb_strtolower(@self, @format)',
            returnOfPhpExecution: ['String'],
            params: [
                new BaseParams('@format', 'string', false, 'UTF-8'),
            ],
        );
    }

    public function replace()
    {
        return new BaseMethods(
            'replace',
            phpCodeForConversion: '\str_replace(@from, @to, @self)',
            returnOfPhpExecution: ['String'],
            params: [
                new BaseParams('@from', 'string', true),
                new BaseParams('@to', 'string', true),
            ],
        );
    }

    public function removeSpaces()
    {
        return new BaseMethods(
            'removeSpaces',
            phpCodeForConversion: [
                'return @characters !== null ? \trim(@self, @characters) : \trim(@self);'
            ],
            returnOfPhpExecution: ['String'],
            params: [
                new BaseParams('@search', 'string|null', false, null),
            ]
        );
    }

    public function removeSpacesLeft()
    {
        return new BaseMethods(
            'removeSpacesLeft',
            phpCodeForConversion: 'return @characters !== null ? \\ltrim(@self, @search) : \\ltrim(@self);',
            returnOfPhpExecution: ['String'],
            params: [
                new BaseParams('@search', 'string|null', false, null),
            ]
        );
    }

    public function removeSpacesRight()
    {
        return new BaseMethods(
            'removeSpacesRight',
            phpCodeForConversion: 'return @characters !== null ? \rtrim(@self, @search) : \rtrim(@self);',
            returnOfPhpExecution: ['String'],
            params: [
                new BaseParams('@search', 'string|null', false, null),
            ]
        );
    }

    public function removeAllSpaces()
    {
        return new BaseMethods(
            'removeAllSpaces',
            phpCodeForConversion: "\preg_replace('/\s+/', '', @self)",
            returnOfPhpExecution: ['String'],
        );
    }

    public function contains()
    {
        return new BaseMethods(
            'contains?',
            phpCodeForConversion: '\str_contains(@self, @search)',
            returnOfPhpExecution: ['Bool'],
            params: [
                new BaseParams('@search', 'string', true),
            ]
        );
    }

    public function endWith()
    {
        return new BaseMethods(
            'endWith?',
            phpCodeForConversion: '\str_ends_with(@self, @search)',
            returnOfPhpExecution: ['Bool'],
            params: [
                new BaseParams('@search', 'string', true),
            ]
        );
    }

    public function startWith()
    {
        return new BaseMethods(
            'startWith?',
            phpCodeForConversion: '\str_starts_with(@self, @search)',
            returnOfPhpExecution: ['Bool'],
            params: [
                new BaseParams('@search', 'string', true),
            ]
        );
    }

    public function decrement()
    {
        return new BaseMethods(
            'decrement',
            phpCodeForConversion: '\str_decrement(@self)',
            returnOfPhpExecution: ['String'],
        );
    }

    public function increment()
    {
        return new BaseMethods(
            'increment',
            phpCodeForConversion: '\str_increment(@self)',
            returnOfPhpExecution: ['String'],
        );
    }

    public function getCsv()
    {
        return new BaseMethods(
            'getCsv',
            phpCodeForConversion: '\str_getcsv(@self, @separator, @enclosure, @escape)',
            returnOfPhpExecution: ['Array'],
            subTypes: ['String'],
            params: [
                new BaseParams('@separator', 'string', false, ','),
                new BaseParams('@enclosure', 'string', false, "\""),
                new BaseParams('@escape', 'string', false, "\\"),
            ]
        );
    }

    public function join()
    {
        return new BaseMethods(
            'join',
            phpCodeForConversion: '@self . \implode(\'\', [@params])',
            returnOfPhpExecution: ['String'],
            params: [
                new BaseParams('@params', 'string', true),
            ]
        );
    }

    public function repeat()
    {
        return new BaseMethods(
            'repeat',
            phpCodeForConversion: '\str_repeat(@self, @times)',
            returnOfPhpExecution: ['String'],
            params: [
                new BaseParams('@times', 'int', true),
            ]
        );
    }

    public function shuffle()
    {
        return new BaseMethods(
            'shuffle',
            phpCodeForConversion: '\str_shuffle(@self)',
            returnOfPhpExecution: ['String'],
        );
    }

    public function splitEvery()
    {
        return new BaseMethods(
            'splitEvery',
            phpCodeForConversion: '\str_split(@self, @counting)',
            returnOfPhpExecution: ['Array'],
            subTypes: ['String'],
            params: [
                new BaseParams('@counting', 'int', false, 1),
            ]
        );
    }

    public function wordCount()
    {
        return new BaseMethods(
            'wordCount',
            phpCodeForConversion: '\str_word_\count(@self, @format, @search)',
            returnOfPhpExecution: ['Int'],
            params: [
                new BaseParams('@format', 'int', false, 0),
                new BaseParams('@search', 'string|null', false, null),
            ]
        );
    }

    public function split()
    {
        return new BaseMethods(
            'split',
            phpCodeForConversion: '\explode(@separator, @self, @limit)',
            returnOfPhpExecution: ['Array'],
            subTypes: ['String'],
            params: [
                new BaseParams('@separator', 'string', true),
                new BaseParams('@limit', 'int', false, PHP_INT_MAX),
            ],
        );
    }

    public function slice()
    {
        return new BaseMethods(
            'slice',
            phpCodeForConversion: [
                'return @length !== null
                ? \mb_substr(@self, @start, @length, "UTF-8")
                : \mb_substr(@self, @start, null, "UTF-8");'
            ],
            returnOfPhpExecution: ['String'],
            params: [
                new BaseParams('@start', 'int', true),
                new BaseParams('@length', 'int|null', false, null),
            ]
        );
    }

    public function substring()
    {
        return new BaseMethods(
            'substring',
            phpCodeForConversion: [
                '$__length = @end !== null ? (@end - @start) : null;',
                'return $__length !== null
                ? \mb_substr(@self, @start, $__length, "UTF-8")
                : \mb_substr(@self, @start, null, "UTF-8");'
            ],
            returnOfPhpExecution: ['String'],
            params: [
                new BaseParams('@start', 'int', true),
                new BaseParams('@end', 'int|null', false, null),
            ]
        );
    }

    public function indexOf()
    {
        return new BaseMethods(
            'indexOf',
            phpCodeForConversion: [
                '$__pos = \mb_strpos(@self, @search, 0, "UTF-8");',
                'return $__pos === false ? -1 : $__pos;'
            ],
            returnOfPhpExecution: ['Int'],
            params: [
                new BaseParams('@search', 'string', true),
            ]
        );
    }

    public function lastIndexOf()
    {
        return new BaseMethods(
            'lastIndexOf',
            phpCodeForConversion: [
                '$__pos = \mb_strrpos(@self, @search, 0, "UTF-8");',
                'return $__pos === false ? -1 : $__pos;'
            ],
            returnOfPhpExecution: ['Int'],
            params: [
                new BaseParams('@search', 'string', true),
            ]
        );
    }

    public function includes()
    {
        return new BaseMethods(
            'includes?',
            phpCodeForConversion: '\str_contains(@self, @search)',
            returnOfPhpExecution: ['Bool'],
            params: [
                new BaseParams('@search', 'string', true),
            ]
        );
    }

    public function replaceAll()
    {
        return new BaseMethods(
            'replaceAll',
            phpCodeForConversion: '\str_replace(@search, @replace, @self)',
            returnOfPhpExecution: ['String'],
            params: [
                new BaseParams('@search', 'string', true),
                new BaseParams('@replace', 'string', true),
            ]
        );
    }

    public function match()
    {
        return new BaseMethods(
            'match',
            phpCodeForConversion: [
                'preg_match_all(@pattern, @self, $__matches);',
                'return $__matches[0] ?? [];'
            ],
            returnOfPhpExecution: ['Array'],
            subTypes: ['String'],
            params: [
                new BaseParams('@pattern', 'string', true),
            ]
        );
    }

    public function reverse()
    {
        return new BaseMethods(
            'reverse',
            phpCodeForConversion: [
                '$__chars = \preg_split("//u", @self, -1, PREG_SPLIT_NO_EMPTY);',
                '$__chars = \array_reverse($__chars);',
                'return \implode("", $__chars);'
            ],
            returnOfPhpExecution: ['String'],
        );
    }

    public function isEmpty()
    {
        return new BaseMethods(
            'isEmpty?',
            phpCodeForConversion: '\mb_strlen(@self, "UTF-8") === 0',
            returnOfPhpExecution: ['Bool'],
        );
    }

    public function padStart()
    {
        return new BaseMethods(
            'padStart',
            phpCodeForConversion: '\str_pad(@self, @length, @pad, STR_PAD_LEFT)',
            returnOfPhpExecution: ['String'],
            params: [
                new BaseParams('@length', 'int', true),
                new BaseParams('@pad', 'string', false, ' '),
            ]
        );
    }

    public function padEnd()
    {
        return new BaseMethods(
            'padEnd',
            phpCodeForConversion: '\str_pad(@self, @length, @pad, STR_PAD_RIGHT)',
            returnOfPhpExecution: ['String'],
            params: [
                new BaseParams('@length', 'int', true),
                new BaseParams('@pad', 'string', false, ' '),
            ]
        );
    }

    public function chars()
    {
        return new BaseMethods(
            'chars',
            phpCodeForConversion: '\preg_split("//u", @self, -1, PREG_SPLIT_NO_EMPTY)',
            returnOfPhpExecution: ['Array'],
            subTypes: ['String'],
        );
    }

    public function words()
    {
        return new BaseMethods(
            'words',
            phpCodeForConversion: [
                '$__words = \preg_split("/\s+/u", \trim(@self));',
                'return \array_values(\array_filter($__words, fn($w) => $w !== ""));'
            ],
            returnOfPhpExecution: ['Array'],
            subTypes: ['String'],
        );
    }

    public function lines()
    {
        return new BaseMethods(
            'lines',
            phpCodeForConversion: '\preg_split("/\r\n|\r|\n/", @self)',
            returnOfPhpExecution: ['Array'],
            subTypes: ['String'],
        );
    }

    public function normalize()
    {
        return new BaseMethods(
            'normalize',
            phpCodeForConversion: '\Normalizer::normalize(@self)',
            returnOfPhpExecution: ['String'],
        );
    }

    public function sliceReplace()
    {
        return new BaseMethods(
            'sliceReplace',
            phpCodeForConversion: [
                '$__before = \mb_substr(@self, 0, @start, "UTF-8");',
                '$__after = \mb_substr(@self, @start + @length, null, "UTF-8");',
                'return $__before . @replace . $__after;'
            ],
            returnOfPhpExecution: ['String'],
            params: [
                new BaseParams('@start', 'int', true),
                new BaseParams('@length', 'int', true),
                new BaseParams('@replace', 'string', true),
            ]
        );
    }

    public function charCodeAt()
    {
        return new BaseMethods(
            'charCodeAt',
            phpCodeForConversion: '\mb_ord(\mb_substr(@self, @index, 1, "UTF-8"), "UTF-8")',
            returnOfPhpExecution: ['Int'],
            params: [
                new BaseParams('@index', 'int', true),
            ]
        );
    }

    public function toNumber()
    {
        return new BaseMethods(
            'toNumber',
            phpCodeForConversion: [
                'return \is_numeric(@self) ? @self + 0 : null;'
            ],
            returnOfPhpExecution: ['Int', 'Float', 'Null'],
        );
    }

    public function toBoolean()
    {
        return new BaseMethods(
            'toBoolean',
            phpCodeForConversion: [
                '$__value = \strtolower(\trim(@self));',
                'return \in_array($__value, ["true", "1", "yes"], true);'
            ],
            returnOfPhpExecution: ['Bool'],
        );
    }

    public function collapseSpaces()
    {
        return new BaseMethods(
            'collapseSpaces',
            phpCodeForConversion: '\preg_replace("/\s+/u", " ", \trim(@self))',
            returnOfPhpExecution: ['String'],
        );
    }

    public function between()
    {
        return new BaseMethods(
            'between',
            phpCodeForConversion: [
                '$__start = \mb_strpos(@self, @from);',
                'if ($__start === false) return null;',
                '$__start += \mb_strlen(@from);',
                '$__end = \mb_strpos(@self, @to, $__start);',
                'if ($__end === false) return null;',
                'return \mb_substr(@self, $__start, $__end - $__start, "UTF-8");'
            ],
            returnOfPhpExecution: ['String', 'Null'],
            params: [
                new BaseParams('@from', 'string', true),
                new BaseParams('@to', 'string', true),
            ]
        );
    }

    public function remove()
    {
        return new BaseMethods(
            'remove',
            phpCodeForConversion: '\str_replace(@search, "", @self)',
            returnOfPhpExecution: ['String'],
            params: [
                new BaseParams('@search', 'string', true),
            ]
        );
    }

    public function to()
    {
        return new BaseMethods(
            'to',
            phpCodeForConversion: [
                'if (\mb_strlen(@self, "UTF-8") !== 1 || \mb_strlen(@to, "UTF-8") !== 1) { return []; }',

                '$__start = \mb_ord(@self, "UTF-8");',
                '$__end = \mb_ord(@to, "UTF-8");',

                '$__result = [];',

                'if ($__start <= $__end) {',
                'for ($__i = $__start; $__i <= $__end; $__i++) {',
                '$__result[] = \mb_chr($__i, "UTF-8");',
                '}',
                '} else {',
                'for ($__i = $__start; $__i >= $__end; $__i--) {',
                '$__result[] = \mb_chr($__i, "UTF-8");',
                '}',
                '}',

                'return $__result;'
            ],
            returnOfPhpExecution: ['Array'],
            subTypes: ['String'],
            params: [
                new BaseParams('@to', 'string', true),
            ]
        );
    }
}
