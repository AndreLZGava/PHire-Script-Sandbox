<?php

namespace PHireScript\Orchestrator;

use PHireScript\Helper\Debug\Debug;
use Sandbox\Samples\error\ErrorMode;
use Sandbox\Samples\success\SuccessMode;
use Sandbox\Samples\warning\WarningMode;

class Orchestrator
{
    public ConfigModifier $config;
    public FileManager $files;

    private array $modeFactory;
    public function __construct()
    {
        $this->config = new ConfigModifier();
        $this->files = new FileManager();

        $this->modeFactory = [
            'success' => new SuccessMode(),
            'warning' => new WarningMode(),
            'error' => new ErrorMode(),
        ];
    }

    public function run($mode, $tags = [])
    {
        $basePath = __DIR__ . '/../samples/' . $mode;
        $cases = scandir($basePath);
        $modeClass = $this->modeFactory[$mode];

        foreach ($cases as $case) {
            if ($case === '.' || $case === '..') {
                continue;
            }

            $casePath = $basePath . '/' . $case;

            if (!is_dir($casePath)) {
                continue;
            }

            $caseFile = $casePath . '/CaseValidation.php';

            if (!file_exists($caseFile)) {
                echo "[SKIP] CaseValidation.php not found in {$casePath}\n";
                continue;
            }

            include_once $caseFile;

            $className = "Sandbox\\Samples\\{$mode}\\{$case}\\CaseValidation";

            if (!class_exists($className)) {
                echo "[SKIP] Class {$className} not found!\n";
                continue;
            }

            $testInstance = new $className($this);

            $reflection = new \ReflectionClass($testInstance);
            $attributes = $reflection->getAttributes(
                \PHireScript\Orchestrator\Attributes\Tag::class
            );

            $caseTags = [];

            foreach ($attributes as $attr) {
                $instance = $attr->newInstance();
                $caseTags[] = $instance->name;
            }

            if (!empty($tags)) {
                $match = array_intersect($tags, $caseTags);

                if (empty($match)) {
                    echo "[SKIP] {$case} don't match tags \n";
                    continue;
                }
            }

            $descriptionAttr = $reflection->getAttributes(
                \PHireScript\Orchestrator\Attributes\Description::class
            );

            $description = null;

            if (!empty($descriptionAttr)) {
                $description = $descriptionAttr[0]->newInstance()->text;
            }

            echo "[RUN] {$case}";
            if ($description) {
                echo " → {$description}";
            }
            echo "\n";
            $this->files->clearOutput();
            $this->config->backup();
            $this->files->copy($casePath, __DIR__ . '/../src/output/');

            $modeClass->before($testInstance);
            $modeClass->execute($testInstance);
            $modeClass->rightAfterFirstExecution($testInstance);
            $modeClass->executeAgain($testInstance);
            $modeClass->after($testInstance);
            $modeClass->executeTest($testInstance);
            $this->config->revert();

            $this->files->clearOutput();
        }
    }
}
