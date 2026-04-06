<?php

declare(strict_types=1);

namespace PHireScript\Core;

use PHireScript\Runtime\RuntimeClass;

class CompilerContext
{
    public function __construct(
        public readonly CompileMode $mode,
        public readonly bool $inMemory = false,
        public readonly bool $verbose = false,
        public readonly bool $clean = false,
        public readonly bool $displayInsideCompiler = false,
        public readonly string $file = '',
        public readonly string $targetWatch = '',
    ) {
    }

    public function shouldPersist(): bool
    {
        return ($this->mode !== CompileMode::CHECK && !$this->inMemory) ||
            $this->mode !== CompileMode::DEBUG;
    }

    public function getExtensionToPersist(): string
    {
        return match ($this->mode) {
            CompileMode::TEST => RuntimeClass::DEFAULT_FILE_TEST_EXTENSION,
            CompileMode::BUILD => RuntimeClass::DEFAULT_FILE_EXTENSION,
            default => RuntimeClass::DEFAULT_FILE_EXTENSION,
        };
    }

    public function processExclusiveFile()
    {
        return $this->mode === CompileMode::BUILD;
    }

    public function persistSnapshot()
    {
         return $this->mode === CompileMode::SNAPSHOT;
    }

    public function isTemporary(): bool
    {
        return $this->mode === CompileMode::CHECK || $this->mode === CompileMode::DEBUG;
    }
}
