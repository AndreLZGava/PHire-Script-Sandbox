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

    public function runSingle($mode, string $case, array $tags = []): void
    {
        $basePath = __DIR__ . '/../samples/' . $mode;
        $modeClass = $this->modeFactory[$mode];
        $casePath = $basePath . '/' . $case;
        $caseFile = $casePath . '/CaseValidation.php';

        if (!is_dir($casePath) || !file_exists($caseFile)) {
            echo "[SKIP] {$case}: CaseValidation.php not found\n";
            return;
        }

        $this->files->clearOutput();
        include_once realpath($caseFile);

        $className = "Sandbox\\Samples\\{$mode}\\{$case}\\CaseValidation";
        // After include, alias the global CaseValidation to its namespaced name
        // so the PSR-4 autoloader doesn't try to include the file a second time
        if (!class_exists($className, false) && class_exists('CaseValidation', false)) {
            class_alias('CaseValidation', $className);
        }
        if (!class_exists($className)) {
            echo "[SKIP] Class {$className} not found\n";
            return;
        }

        $testInstance = new $className($this);
        $reflection = new \ReflectionClass($testInstance);

        $caseTags = [];
        foreach ($reflection->getAttributes(\PHireScript\Orchestrator\Attributes\Tag::class) as $attr) {
            $caseTags[] = $attr->newInstance()->name;
        }

        if (!empty($tags) && empty(array_intersect($tags, $caseTags))) {
            echo "[SKIP] {$case} don't match tags\n";
            return;
        }

        $descAttrs = $reflection->getAttributes(\PHireScript\Orchestrator\Attributes\Description::class);
        $description = !empty($descAttrs) ? $descAttrs[0]->newInstance()->text : null;

        echo "[RUN] {$case}" . ($description ? " → {$description}" : '') . "\n";

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

    public function run($mode, $tags = [], ?int $from = null, ?int $to = null)
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

            if ($from !== null || $to !== null) {
                $caseNum = (int) preg_replace('/[^0-9]/', '', $case);
                if ($from !== null && $caseNum < $from) {
                    continue;
                }
                if ($to !== null && $caseNum > $to) {
                    continue;
                }
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
