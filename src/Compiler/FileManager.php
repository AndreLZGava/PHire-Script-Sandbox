<?php

declare(strict_types=1);

namespace PHireScript\Compiler;

use Exception;
use FilesystemIterator;
use PHireScript\Core\CompileMode;
use PHireScript\Core\CompilerContext;
use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\Exceptions\CompileException;
use PHireScript\Runtime\RuntimeClass;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use PHireScript\Runtime\Types\MetaTypes;
use PHireScript\Runtime\Types\SuperTypes;
use ReflectionClass;
use RuntimeException;
use Throwable;

class FileManager
{
    public function __construct(private readonly CompilerContext $context)
    {
    }

    public function loadAndCompile($sourceDir, $distDir, $transpiler)
    {
        if ($this->context->mode === CompileMode::WATCH) {
            $this->watch($sourceDir, $distDir, $transpiler);
            return;
        }
        $directory = new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            if (
                empty($this->context->file) &&
                $file->getExtension() === $this->context->getExtensionToPersist() ||
                $this->context->file === $file->getPathname()
            ) {
                $relativePath = substr($file->getPathname(), strlen($sourceDir));

                $outputFile = $distDir  . \str_replace(
                    '.' . $this->context->getExtensionToPersist(),
                    '.php',
                    $relativePath
                );
                $this->compileFile($file->getPathname(), $outputFile, $transpiler);
            }
        }
    }

    private function watch($sourceDir, $distDir, $transpiler)
    {
        $extension = $this->context->getExtensionToPersist();
        $targetDir = $this->context->targetWatch;
        echo "--- PHireScript started the process ---\n";
        echo "Watching files .$extension in: $targetDir\n";

        while (true) {
            try {
                $directory = new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS);
                $iterator = new RecursiveIteratorIterator($directory);

                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getExtension() === $extension) {
                        try {
                            $filePath = $file->getRealPath();
                            $relativePath = substr($file->getPathname(), strlen($sourceDir) + 1);
                            $currentHash = md5_file($filePath);

                            $outputFile = $distDir . '/' .
                                \str_replace(
                                    '.' . RuntimeClass::DEFAULT_FILE_EXTENSION,
                                    '.php',
                                    $relativePath
                                );
                            $outputSubDir = dirname($outputFile);

                            if (!is_dir($outputSubDir)) {
                                mkdir($outputSubDir, 0755, true);
                            }

                            if (!isset($filesHash[$filePath]) || $filesHash[$filePath] !== $currentHash) {
                                if (isset($filesHash[$filePath])) {
                                    echo "[" . date('H:i:s') . "] Changes found in : " . $filePath . "\n";
                                    $this->compileFile($file->getPathname(), $outputFile, $transpiler);
                                } else {
                                    echo "[" . date('H:i:s') . "] Watching: " . $filePath . "\n";
                                }

                                $filesHash[$filePath] = $currentHash;
                            }
                        } catch (Throwable $e) {
                            echo "\033[1;31m✗ Error processing $filePath: " . $e->getMessage() . "\033[0m\n";
                        }
                    }
                }
            } catch (Throwable $e) {
                echo "\033[1;31m✗ Watcher error: " . $e->getMessage() . "\033[0m\n";
            }

            clearstatcache();
            usleep(900000);
        }
    }

    public function load($sourceDir, $transpiler): array
    {
        $directory = new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($directory);
        $result = [];
        foreach ($iterator as $file) {
            if ($file->getExtension() === RuntimeClass::DEFAULT_FILE_EXTENSION) {
                try {
                    $sourceCode = file_get_contents($file->getPathname());
                    $result[] = $transpiler->compile($sourceCode, $file->getPathname());
                } catch (Exception $e) {
                    echo "\033[1;31m✗ Error in {$file->getPathname()}: " . $e->getMessage() . "\033[0m\n";
                    $this->getErrorInterface($e, $transpiler, $sourceCode);
                }
            }
        }
        return $result;
    }


    private function compileFile($input, $output, $transpiler)
    {
        try {
            $sourceCode = file_get_contents($input);
            $result = $transpiler->compile($sourceCode, $input);
            if ($this->context->shouldPersist()) {
                $outputSubDir = dirname($output);
                if (!is_dir($outputSubDir)) {
                    mkdir($outputSubDir, 0755, true);
                }
                file_put_contents($output, $result);

                if ($this->context->persistSnapshot()) {
                    $preParserCode = $transpiler->getCodeBeforeGenerator();
                    $preCompiledCode = \str_replace(
                        '.' . RuntimeClass::DEFAULT_FILE_EXTENSION,
                        '.' . RuntimeClass::DEFAULT_FILE_SNAPSHOT_EXTENSION,
                        $input
                    );
                    file_put_contents($preCompiledCode, $preParserCode);
                    echo "\n\033[1;32m✔ $input -> $preCompiledCode\033[0m\n";
                }

                $output_text = [];
                $return_var = 0;
                exec("php -l " . escapeshellarg($output), $output_text, $return_var);
                if ($return_var !== 0) {
                    echo "✗ Syntax Error in generated file $output:\n";
                    echo \implode("\n", $output_text) . "\n";
                } else {
                    echo "\n\033[1;32m✔ $input -> $output\033[0m\n";
                }
            }

            if ($this->context->inMemory && !$this->context->displayInsideCompiler) {
                echo "\n\033[1;32m✔ SUCCESSFUL PHP OUTPUT\033[0m\n";
                echo $result . "\n";
            }
            if ($this->context->inMemory && $this->context->displayInsideCompiler) {
                $this->getExecutionInterface($sourceCode, $result);
            }
        } catch (Exception $e) {
            if ($this->context->displayInsideCompiler) {
                $this->getErrorInterfaceWeb($e, $transpiler, $sourceCode);
                return;
            }
            echo "\033[1;31m✗ Error in $input: " . $e->getMessage() . "\033[0m\n";
            $this->getErrorInterface($e, $transpiler, $sourceCode);
        }
    }


    public function getConfigFile()
    {
        $configs = json_decode(file_get_contents('PHireScript.json'), true);
        $configs['php'] = phpversion();
        $configs['metatypes'] = $this->listClassesExtending(
            __DIR__ . '/../../src/Runtime/Types/MetaTypes/',
            MetaTypes::class
        );
        $configs['supertypes'] = $this->listClassesExtending(
            __DIR__ . '/../../src/Runtime/Types/SuperTypes/',
            SuperTypes::class
        );


        return $configs;
    }

    private function getErrorInterface($e, $transpiler, $code)
    {
        $width = (int) shell_exec('tput cols') ?: 120;

        $gutterWidth = 10;
        $availableWidth = $width - $gutterWidth;

        $red    = "\033[1;31m";
        $blue   = "\033[1;34m";
        $cyan   = "\033[1;36m";
        $gray   = "\033[0;90m";
        $yellow = "\033[1;33m";
        $reset  = "\033[0m";

        $codeGenerated = $transpiler->getCodeBeforeGenerator();

        $hasTranspiled = !empty(\trim($codeGenerated));

        $originalLines = \explode("\n", \rtrim($code));
        $preParserLines = $hasTranspiled ? \explode("\n", \rtrim($codeGenerated)) : [];
        $maxLines = \max(\count($originalLines), \count($preParserLines));
        $errorLine = 0;
        $message = $e->getMessage();
        if ($e instanceof CompileException) {
            $errorLine = $e->line;
        }

        echo "\n{$red}" . \str_repeat('=', $width) . "{$reset}\n";
        echo "  {$red}PHire Script DEBUGGER - COMPILATION ERROR{$reset}\n";
        echo "{$red}" . \str_repeat('=', $width) . "{$reset}\n\n";

        if ($hasTranspiled) {
            $colWidth = (int) ($availableWidth / 2) - 2;
            \printf(
                " %-4s | %-{$colWidth}s | %s\n",
                "Line",
                "{$blue}ORIGINAL PHire Script{$reset}",
                "{$cyan}TRANSPILED PHP{$reset}"
            );
        } else {
            $colWidth = $availableWidth;
            \printf(" %-4s | %s\n", "Line", "{$blue}ORIGINAL PHire Script (Full View){$reset}");
        }

        echo \str_repeat('-', $width) . "\n";

        for ($i = 0; $i < $maxLines; $i++) {
            $currentLineNum = $i + 1;
            $left  = $originalLines[$i] ?? '';
            $right = $preParserLines[$i] ?? '';

            $indicator = ($currentLineNum === $errorLine) ? "{$red}→{$reset}" : " ";
            $lineNumColor = ($currentLineNum === $errorLine) ? $red : $gray;

            if ($hasTranspiled) {
                \printf(
                    " %s %s%-3d%s | %s%-{$colWidth}s%s | %s%s%s\n",
                    $indicator,
                    $lineNumColor,
                    $currentLineNum,
                    $reset,
                    $blue,
                    \mb_substr($left, 0, $colWidth),
                    $reset,
                    $cyan,
                    \mb_substr($right, 0, $colWidth),
                    $reset
                );
            } else {
                \printf(
                    " %s %s%-3d%s | %s%s%s\n",
                    $indicator,
                    $lineNumColor,
                    $currentLineNum,
                    $reset,
                    $blue,
                    $left,
                    $reset
                );
            }
        }

        echo "\n{$yellow}ERROR MESSAGE:{$reset}\n";
        echo "{$red}» {$message}{$reset}\n";
        echo "{$red}" . str_repeat('=', $width) . "{$reset}\n";
    }

    private function getErrorInterfaceWeb($e, $transpiler, $code): string
    {
        $width = 120;
        $gutterWidth = 8;
        $availableWidth = $width - $gutterWidth;

        $codeGenerated = $transpiler->getCodeBeforeGenerator();
        $hasTranspiled = !empty(\trim($codeGenerated));

        $originalLines   = \explode("\n", \rtrim($code));
        $preParserLines  = $hasTranspiled ? \explode("\n", \rtrim($codeGenerated)) : [];
        $maxLines        = \max(\count($originalLines), \count($preParserLines));

        $message = htmlspecialchars($e->getMessage());
        $errorLine = ($e instanceof CompileException) ? $e->line : null;

        $html = '';

        $html .= '<pre style="
        background:#0d1117;
        color:#c9d1d9;
        padding:20px;
        border-radius:10px;
        font-family: Consolas, monospace;
        font-size:14px;
        overflow:auto;
    ">';

        $html .= "PHire Script DEBUGGER - COMPILATION ERROR\n";
        $html .= \str_repeat('=', $width) . "\n\n";

        if ($hasTranspiled) {
            $colWidth = (int) ($availableWidth / 2) - 2;
            $html .= \str_pad('Line', 6) .
                \str_pad('ORIGINAL PHire Script', $colWidth) .
                " | TRANSPILED PHP\n";
        } else {
            $colWidth = $availableWidth;
            $html .= \str_pad('Line', 6) .
                "ORIGINAL PHire Script\n";
        }

        $html .= \str_repeat('-', $width) . "\n";

        for ($i = 0; $i < $maxLines; $i++) {
            $currentLineNum = $i + 1;
            $left  = \htmlspecialchars($originalLines[$i] ?? '');
            $right = \htmlspecialchars($preParserLines[$i] ?? '');

            $isError = ($currentLineNum === $errorLine);

            $linePrefix = str_pad((string) $currentLineNum, 4, ' ', STR_PAD_LEFT) . ' ';

            if ($hasTranspiled) {
                $line =
                    $linePrefix .
                    str_pad(mb_substr($left, 0, $colWidth), $colWidth) .
                    " | " .
                    mb_substr($right, 0, $colWidth);
            } else {
                $line = $linePrefix . $left;
            }

            if ($isError) {
                $html .= '<span style="background:#3b0d0d;color:#ff7b72;">→ ' . $line . "</span>\n";
            } else {
                $html .= '  ' . $line . "\n";
            }
        }

        $html .= "\n\nERROR MESSAGE:\n";
        $html .= '<span style="color:#ff7b72;">» ' . $message . "</span>\n";
        $html .= str_repeat('=', $width);

        $html .= '</pre>';

        echo $html;
        exit;
    }

    private function getExecutionInterface(string $compiledCode, string $executionResult)
    {
        $compiledSafe = htmlspecialchars($compiledCode);
        $resultSafe   = htmlspecialchars($executionResult);

        echo '
    <div style="
        display:flex;
        gap:20px;
        align-items:flex-start;
        font-family:Consolas, monospace;
    ">
        <div style="flex:1;">
            <h3 style="margin:0 0 10px 0;color:#58a6ff;">PHire Script</h3>
            <pre style="
                background:#0d1117;
                color:#c9d1d9;
                padding:20px;
                border-radius:10px;
                overflow:auto;
                min-height:400px;
            ">' . $compiledSafe . '</pre>
        </div>

        <div style="flex:1;">
            <h3 style="margin:0 0 10px 0;color:#3fb950;">PHP result</h3>
            <pre style="
                background:#0d1117;
                color:#c9d1d9;
                padding:20px;
                border-radius:10px;
                overflow:auto;
                min-height:400px;
            ">' . $resultSafe . '</pre>
        </div>
    </div>
    ';
    }

    private function listClassesExtending(
        string $directory,
        string $baseClass
    ): array {
        $directory = realpath($directory);

        if ($directory === false || !is_dir($directory)) {
            throw new RuntimeException("Invalid path: {$directory}");
        }

        if (!class_exists($baseClass)) {
            throw new RuntimeException("Base class does not exists: {$baseClass}");
        }

        $classes = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $directory,
                FilesystemIterator::SKIP_DOTS
            )
        );
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            $tokens  = token_get_all($content);

            $namespace = '';
            $class     = null;
            $extends   = null;


            for ($i = 0; $i < \count($tokens); $i++) {
                if ($tokens[$i][0] === T_NAMESPACE) {
                    $namespace = '';
                    for ($j = $i + 2; isset($tokens[$j]); $j++) {
                        if ($tokens[$j] === ';') {
                            break;
                        }
                        $namespace .= $tokens[$j][1];
                    }
                }

                if ($tokens[$i][0] === T_CLASS) {
                    $class = $tokens[$i + 2][1] ?? null;

                    for ($j = $i; isset($tokens[$j]); $j++) {
                        if (is_array($tokens[$j]) && $tokens[$j][0] === T_EXTENDS) {
                            $extends = '';
                            for ($k = $j + 2; isset($tokens[$k]); $k++) {
                                if (\in_array($tokens[$k][0], [T_STRING, T_NS_SEPARATOR])) {
                                    $extends .= $tokens[$k][1];
                                } else {
                                    break;
                                }
                            }
                            break;
                        }
                    }
                    break;
                }
            }
            if (!$class) {
                continue;
            }

            $fqcn = $namespace ? "$namespace\\$class" : $class;

            if (!class_exists($fqcn)) {
                require_once $file->getPathname();
            }

            if (!class_exists($fqcn)) {
                continue;
            }

            $ref = new ReflectionClass($fqcn);

            if ($ref->isSubclassOf($baseClass)) {
                $classes[] = $fqcn;
            }
        }

        return $classes;
    }

    private function cleanDirectory($dir)
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
    }
}
