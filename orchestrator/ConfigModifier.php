<?php

namespace PHireScript\Orchestrator;

use Exception;

class ConfigModifier
{
    private string $original = '';
    private bool $modifying = false;
    private string $filePath;

    public function __construct(string $fileName = 'PHireScript.json')
    {
        $this->filePath = __DIR__ . '/../' . $fileName;
    }

    public function backup(): void
    {
        if (!$this->modifying) {
            if (!file_exists($this->filePath)) {
                throw new Exception("Arquivo {$this->filePath} não encontrado.");
            }
            $this->original = file_get_contents($this->filePath);
            $this->modifying = true;
        }
    }

    public function revert(): void
    {
        if ($this->modifying) {
            file_put_contents($this->filePath, $this->original);
            $this->modifying = false;
            $this->original = '';
        }
    }

    public function getOriginal(): ?string
    {
        return $this->original;
    }

    public function getModified(): ?string
    {
        if (file_exists($this->filePath)) {
            return file_get_contents($this->filePath);
        }
        return null;
    }

    public function setModified(string $newContent): void
    {
        file_put_contents($this->filePath, $newContent);
    }
}
