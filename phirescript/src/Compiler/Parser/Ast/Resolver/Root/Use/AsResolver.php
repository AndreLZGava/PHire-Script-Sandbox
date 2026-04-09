<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Resolver\Root\Use;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Resolver\ContextTokenResolver;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\ParseContext;

class AsResolver implements ContextTokenResolver {
  public function isTheCase(Token $token, ParseContext $parseContext, AbstractContext $context): bool {
    return $token->value === 'as';
  }

  public function resolve(
    Token $token,
    ParseContext $parseContext,
    AbstractContext $context
  ): void {
    $context->alias = true;
  }
}
