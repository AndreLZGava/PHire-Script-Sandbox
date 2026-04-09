<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Processors;

class AccessorHandler implements PreprocessorInterface
{
    public function process(string $code): string
    {
        $code = preg_replace('/(?<!\d)\.|\.(?!\d)/', '->', $code);
        $code = preg_replace('/(["\'])\s*\+\s*|\s*\+\s*(["\'])/', '$1 . $2', (string) $code);
        $code = \str_replace('var ', '', $code);
        return $code;
    }
}
