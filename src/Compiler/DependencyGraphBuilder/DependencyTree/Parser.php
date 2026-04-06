<?php

namespace PHireScript\Compiler\DependencyGraphBuilder\DependencyTree;

use PHireScript\Compiler\Parser\Ast\Context\Root\ProgramContext;
use PHireScript\Compiler\Parser\Managers\ContextManager;
use PHireScript\Compiler\Parser\Managers\SymbolTableManager;
use PHireScript\Compiler\Parser\Managers\TokenManager;
use PHireScript\Compiler\Parser\Managers\VariableManager;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Compiler\Program;
use PHireScript\DependencyGraphBuilder;
use PHireScript\Runtime\RuntimeClass;

class Parser
{
    public function __construct(private array $config, protected DependencyGraphBuilder $dependencyBuilder)
    {
    }

    public function parse($tokens, $path): Program
    {
        $tokenManager = new TokenManager(
            RuntimeClass::CONTEXT_PRE_BUILD,
            $tokens,
            0
        );

        $program = new Program($tokenManager->getCurrentToken());

        $parseContext = new ParseContext(
            variables: new VariableManager(),
            program: $program,
            tokenManager: $tokenManager,
            contextManager: null,
            symbolTable: new SymbolTableManager(),
            dependencyBuilder: $this->dependencyBuilder,
        );

        $rootContext = new ProgramContext($parseContext);

        $contextManager = new ContextManager($rootContext);

        $parseContext->contextManager = $contextManager;

        $contextManager->setPath($path);
        $contextManager->setConfig($this->config);

        while (!$tokenManager->isEndOfTokens()) {
            $token = $tokenManager->getCurrentToken();

            $contextManager->handle($token, $parseContext);

            $tokenManager->advance();
        }
        return $program;
    }
}
