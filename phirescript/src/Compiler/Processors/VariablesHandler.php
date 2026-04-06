<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Processors;

class VariablesHandler implements PreprocessorInterface
{
    public function process(string $code): string
    {
        $code = preg_replace_callback('/\((.*?)\)/', function ($matches) {
            $params = \explode(',', $matches[1]);
            $fixedParams = array_map(function ($param) {
                $p = \trim($param);
                if (
                    empty($p) ||
                    str_starts_with($p, '$') ||
                    \str_contains($p, '"') ||
                    \str_contains($p, "'") ||
                    is_numeric($p) ||
                    preg_match('/^[^a-zA-Z_\x7f-\xff]/', $p)
                ) {
                    return $param;
                }
                return '$' . $p;
            }, $params);
            return '(' . \implode(', ', $fixedParams) . ')';
        }, $code);
        return $code;
    }
}
