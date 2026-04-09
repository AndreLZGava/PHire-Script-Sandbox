<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use Exception;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\Exceptions\CompileException;
use PHireScript\Runtime\RuntimeClass;

class PackageNode extends Statement {
    public string $namespace;
    public string $completeObjectReference;

    public string $completePackage;

    public function __construct(
        public Token $token,
        public string $file,
        public ?string $package = null,
        public ?string $object = null,
    ) {
        //$this->validate();
    }

    public function validate() {
        $basename = basename($this->file);
        $ext = RuntimeClass::DEFAULT_FILE_EXTENSION;
        $textExt = RuntimeClass::DEFAULT_FILE_TEST_EXTENSION;

        if (is_null($this->package)) {
            throw new CompileException(
                'Package must be defined!',
                $this->token->line,
                $this->token->column,
            );
        }
        if (
            !str_starts_with($basename, $this->object) &&
            (
                \str_ends_with($basename, '.' . $ext) ||
                \str_ends_with($basename, '.' . $textExt)
            )
        ) {
            $message = \str_ends_with($basename, '.' . $ext) ?
                'File name must match class/interface/type/' .
                'immutable/trait name! File ' . $this->file .
                ' object name ' . $this->object :
                'File name must match class/validate ' .
                'name! File ' . $this->file . ' object name ' . $this->object;
            throw new CompileException(
                $message,
                $this->token->line,
                $this->token->column,
            );
        }
    }

    public function generateNamespace(ParseContext $context): void {
        $config = $context->contextManager->getConfig();
        $namespace = '';
        $namespace = \current(\explode('/' . $this->object, $this->file));
        $baseDir = \rtrim((string) $config['paths']['source'], '/') . '/';

        if (str_starts_with($namespace, $baseDir)) {
            $namespace = substr($namespace, strlen($baseDir));
        }

        $namespace = \str_replace('/', '\\', $namespace);

        $this->completePackage = $this->package . '.' . $this->object;
        $this->namespace = $config['namespace'] . '\\' . $namespace;
        $this->completeObjectReference = '\\' . $this->namespace . '\\' . $this->object . '::class';
        $context->setCurrentPackage($this->completePackage);
    }
}
