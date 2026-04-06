<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Resolver\Declaration;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Context\Declarations\ClassContext;
use PHireScript\Compiler\Parser\Ast\Resolver\ContextTokenResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\ClassNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Helper\Debug\Debug;

class TypeResolver implements ContextTokenResolver {
  public function isTheCase(Token $token, ParseContext $parseContext, AbstractContext $context): bool {
    return $token->value === 'type';
  }

  public function resolve(
    Token $token,
    ParseContext $parseContext,
    AbstractContext $context
  ): void {
    $node = new ClassNode(
      token: $token,
    );

    $parseContext->contextManager->enter(
      new ClassContext($node)
    );

    $context->addChild($node);
  }
}
