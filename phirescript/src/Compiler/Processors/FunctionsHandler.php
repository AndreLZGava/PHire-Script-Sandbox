<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Processors;

class FunctionsHandler implements PreprocessorInterface
{
    public function process(string $code): string
    {
        $code = preg_replace_callback('/(?<!->)\((.*?)\)\s*=>\s*(\{?)/', function ($matches) {
            $params = $matches[1];
            $hasBrace = ($matches[2] === '{');
            return $hasBrace ? "function($params) {" : "fn($params) => ";
        }, $code);
        $pattern = '/(?<!function|func|if|while|for|foreach|switch|catch|return' .
        '|->)\b([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*\((.*?)\)\s*\{/s';

        $code = preg_replace($pattern, 'function $1($2) {', (string) $code);

        $code = \str_replace('function eachfunction', 'each', $code);
        $code = \str_replace('function mapfunction', 'each', $code);
        $code = preg_replace('/each\s*\(\s*\((.*?)\)\s*\{/', 'each(function($1) {', $code);
        $code = preg_replace('/map\s*\(\s*\((.*?)\)\s*\{/', 'map(function($1) {', (string) $code);
        $code = \str_replace('function fn(', 'fn(', $code);
        return $code;
    }
}
