<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Processors;

class NativeTypesHandler implements PreprocessorInterface
{
    public function process(string $code): string
    {
        $typeMap = [
        'Bool'   => 'bool',
        'Int'    => 'int',
        'Float'  => 'float',
        'String' => 'string',
        'Array'  => 'array',
        'Object' => 'object',
        'Void'   => 'void'
        ];

        foreach ($typeMap as $psType => $phpType) {
            $code = preg_replace('/\b' . $psType . '\s*\((.*?)\)/', '(' . $phpType . ')($1)', (string) $code);
        }

        foreach ($typeMap as $psType => $phpType) {
            $code = preg_replace('/:\s*' . $psType . '\b/', ': ' . $phpType, (string) $code);
        }

        return $code;
    }
}
