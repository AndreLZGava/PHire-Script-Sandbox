<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Resolver\Expressions;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Resolver\ContextTokenResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\CommentNode;
use PHireScript\Compiler\Parser\Ast\Nodes\GlobalConstNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\ParseContext;

class GlobalConstantResolver implements ContextTokenResolver {
  public function isTheCase(Token $token, ParseContext $parseContext, AbstractContext $context): bool {
    return $token->isGlobalConst();
  }

  public function resolve(
    Token $token,
    ParseContext $parseContext,
    AbstractContext $context
  ): void {
    if (!$this->hasDefinition($token)) {
      trigger_error("Value {$token->value} has been recognized as a const. " .
        "But we could not find it in this environment. Please verify", E_USER_WARNING);
    }
    $globalConstNode = new GlobalConstNode($token);
    $context->addChild(
      $globalConstNode
    );
  }

  private function hasDefinition($token): bool {
    $constants = get_defined_constants(true);

    foreach ($constants as $category => $list) {
      foreach ($list as $name => $value) {
        if ($name === $token->value) {
          return true;
        }
      }
    }

    return false;
  }
}
