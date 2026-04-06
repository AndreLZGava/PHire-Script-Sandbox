<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Processors;

class VariablesBeforeInitializationHandler implements PreprocessorInterface
{
    public function process(string $code): string
    {
        $reserved = [
            'string',
            'int',
            'float',
            'bool',
            'array',
            'object',
            'void',
            'php',
            'echo',
            'return',
            'if',
            'else',
            'foreach',
            'for',
            'while',
            'new',
            'null',
            'true',
            'false',
            'stdClass',
            'fn',
            'function',
            'class',
            'static',
            'public',
            'protected',
            'private',
            'namespace',
            'use',
            'extends',
            'implements'
        ];

        $pattern = '/(?:\/\/.*|\/\*[\s\S]*?\*\/|(?:"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"|' .
            '\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'))|(?<!\$|->|class\s|function\s|new\s|' .
            'extends\s|implements\s|namespace\s|use\s)\b([a-zA-Z_\x7f-\xff]' .
            '[a-zA-Z0-9_\x7f-\xff]*)\b(?!\s*\()/i';

        $code = preg_replace_callback($pattern, function ($matches) use ($reserved) {
            if (!isset($matches[1]) || empty($matches[1])) {
                return $matches[0];
            }

            $word = $matches[1];

            if (
                \in_array(strtolower($word), $reserved, true) ||
                \is_numeric($word) ||
                \str_starts_with($word, '__OBJ_')
            ) {
                return $word;
            }

            return '$' . $word;
        }, $code);

        $code = preg_replace('/<\?\s*\$php/', '<?php', (string) $code);
        $code = \str_replace(
            ['($bool)', '($int)', '($string)', '($array)', '($object)', '($float)'],
            ['(bool)', '(int)', '(string)', '(array)', '(object)', '(float)'],
            $code
        );

        return $code;
    }
}
