<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser;

use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\Managers\TokenManager;
use PHireScript\Compiler\Parser\Managers\VariableManager;
use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\RuntimeClass;

class ParserDispatcher
{
    private array $emitters = [];

    public function __construct(iterable $emitters)
    {
        foreach ($emitters as $emitter) {
            $this->emitters[] = $emitter;
        }
    }

    public function emit(Token $token, ParseContext $context): ?Node
    {
        foreach ($this->emitters as $emitter) {
            if ($emitter->isTheCase($token, $context)) {
                //Debug::show([\get_class($emitter), $token]);
                $result = $emitter->process($token, $context);
                $context->tokenManager->advance();
                return $result;
            }
        }
        Debug::show($token, $context->tokenManager->getLeftTokens(5));
        exit;
        //return "// Unknown node: {$node::class}\n";
    }
}
