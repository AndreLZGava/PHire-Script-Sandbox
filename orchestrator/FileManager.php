<?php

namespace PHireScript\Orchestrator;

use Exception;
use PHireScript\Helper\Debug\Debug;

class FileManager
{
    private string $outputPath;

    public function __construct()
    {
        $this->outputPath = realpath(__DIR__ . '/../src');

        if (!$this->outputPath) {
            throw new Exception("Output directory not found.");
        }
    }


    public function clearOutput(): void
    {
        $files = scandir($this->outputPath);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $fullPath = $this->outputPath . DIRECTORY_SEPARATOR . $file;

            $this->delete($fullPath);
        }
    }

    public function move(string $from, string $to): void
    {
        if (!file_exists($from)) {
            throw new Exception("Source file does not exist: {$from}");
        }

        $dir = dirname($to);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if (!rename($from, $to)) {
            throw new Exception("Failed to move file from {$from} to {$to}");
        }
    }

    public function copy(string $from, string $to): void
    {
        if (!file_exists($from)) {
            throw new \Exception("Source does not exist: {$from}");
        }

        if (is_dir($from)) {
            $this->copyDirectoryContents($from, $to);
            return;
        }

        $dir = dirname($to);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if (!copy($from, $to)) {
            throw new \Exception("Failed to copy file from {$from} to {$to}");
        }
    }

    private function copyDirectoryContents(string $from, string $to): void
    {
        if (!is_dir($to)) {
            mkdir($to, 0777, true);
        }

        $files = scandir($from);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $sourcePath = $from . DIRECTORY_SEPARATOR . $file;
            $destPath = $to . DIRECTORY_SEPARATOR . $file;

            if (is_dir($sourcePath)) {
                $this->copyDirectoryContents($sourcePath, $destPath);
            } else {
                if (!copy($sourcePath, $destPath)) {
                    throw new \Exception("Failed to copy file: {$sourcePath}");
                }
            }
        }
    }

    private function delete(string $path): void
    {
        if (is_dir($path)) {
            $files = scandir($path);

            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $this->delete($path . DIRECTORY_SEPARATOR . $file);
            }

            rmdir($path);
        } else {
            unlink($path);
        }
    }
}
