<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Processors;

interface PreprocessorInterface
{
    public function process(string $code): string;
}
