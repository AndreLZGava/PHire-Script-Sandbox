# Data Model: this Keyword and Self Return Type

**Date**: 2026-06-04

## Existing Nodes (no changes needed)

### `ThisExpressionNode`
- **File**: `src/Compiler/Parser/Ast/Nodes/Expressions/ThisExpressionNode.php`
- **Purpose**: AST node representing the `this` keyword
- **State**: Complete — extends `Expression`, no fields needed

### `PropertyAccessNode`
- **File**: `src/Compiler/Parser/Ast/Nodes/Expressions/PropertyAccessNode.php`
- **Purpose**: Represents `object.property` access (used for `this.propName`)
- **Fields**: `object: Node`, `property: Node|string`
- **State**: Complete — already used by `ExternalPropertyAccessResolver`

### `ReturnTypeNode`
- **File**: `src/Compiler/Parser/Ast/Nodes/Signatures/ReturnTypeNode.php`
- **Purpose**: Carries the list of return type strings for a method
- **State**: Complete — `Self` is stored as a string in `types[]`, handled specially at emit time

## New Files Required

### `ThisResolver`
- **File**: `src/Compiler/Parser/Ast/Resolver/Expressions/ThisResolver.php`
- **Implements**: `ContextTokenResolver`
- **isTheCase**: `$token->value === 'this'` (token is `T_KEYWORD`)
- **resolve**: Creates `ThisExpressionNode($token)`, calls `$parseContext->variables->setVirtualVariable($node)`, adds to context children
- **Used by**: `MethodScopeContext`, `IfScopeContext`, `ElseScopeContext`, `ElseIfScopeContext`, `TryScopeContext`, `HandleScopeContext`, `AlwaysScopeContext`, `ReturnContext`, `ArrowFunctionDeclarationContext`

### `ThisPropertyAccessResolver`
- **File**: `src/Compiler/Parser/Ast/Resolver/Expressions/ThisPropertyAccessResolver.php`
- **Implements**: `ContextTokenResolver`
- **isTheCase**: token is identifier + next token is NOT `(` + focus is `ThisExpressionNode`
- **resolve**: Creates `PropertyAccessNode($token, $focus, $token->value)`, sets as virtual variable, adds to context children
- **Used by**: Same scope contexts as `ThisResolver` (placed after `ThisResolver` + `DotResolver`)

### `ThisScopeChecker`
- **File**: `src/Compiler/Checker/Expression/ThisScopeChecker.php`
- **Implements**: `Checker` with `#[CompilerPass(order: N)]`
- **mustCheck**: `$node instanceof ThisExpressionNode`
- **check**:
  1. Verify `$parseContext->contextManager->isIn(ClassContext::class)` (or TypeContext/ImmutableContext) — throw `CheckerException` if not
  2. *(property validation — Phase 2, can be deferred to a follow-up)* 

## Modified Files

### `ReturnTypeContext.php`
- Add recognition of `token->value === 'Self'` (a `T_KEYWORD`) in the handler
- Simplest: add a `SelfReturnTypeResolver` inline or extend `TypeResolver::isTheCase()` to also match `$token->isKeyword() && $token->value === 'Self'`

### `ReturnTypeEmitter.php`
- Before the `mb_strtolower` loop, check if `$type === 'Self'` → push `'static'` instead

### All scope contexts (listed in research.md)
- Add `new ThisResolver()` before `FunctionCallResolver()` in resolvers list
- Add `new ThisPropertyAccessResolver()` after `ThisResolver()` and `DotResolver()`

### `ReturnContext.php`
- Add `new ThisResolver()` to resolvers list (enables `return this`)

### `ArrowFunctionDeclarationContext.php`
- Add `new ThisResolver()` and `new ThisPropertyAccessResolver()` to resolvers list

## Scope Validation Logic (ThisScopeChecker)

```
ClassContext hierarchy for 'this' validity:
  ProgramContext
    └── ClassContext / TypeContext / ImmutableContext   ← 'this' valid from here down
          └── ClassBodyContext
                └── MethodScopeContext
                      └── IfScopeContext / TryScopeContext / ArrowFunctionDeclarationContext / ...
```

`ContextManager::isIn(ClassContext::class)` traverses parents and returns `true` if any ancestor is a `ClassContext`. This covers all nesting depths including arrow functions inside methods inside classes.

The same check works for `TypeContext` and `ImmutableContext` since they also compile to PHP classes.
