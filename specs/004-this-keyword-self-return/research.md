# Research: this Keyword and Self Return Type

**Date**: 2026-06-04

## Existing Infrastructure (found by code reading)

### Already present — no work needed

| Artifact | Location | Notes |
|----------|----------|-------|
| `T_KEYWORD` scanner pattern includes `this` | `Scanner.php:26` | Already tokenized |
| `ThisExpressionNode` | `Parser/Ast/Nodes/Expressions/ThisExpressionNode.php` | Empty expression node |
| `ThisExpressionEmitter` | `Emitter/Expressions/ThisExpressionEmitter.php` | Emits `$this`, registered in `Emitter.php:125` |
| `ContextManager::isIn()` | `Parser/Managers/ContextManager.php:52` | Traverses parent context chain — perfect for scope check |
| `DotResolver` | `Parser/Ast/Resolver/Statements/DotResolver.php` | Sets virtual variable on `.` — works with any node as focus |

### Missing — must be created or modified

| Gap | Where | Resolution |
|-----|-------|------------|
| No `ThisResolver` to produce `ThisExpressionNode` from token | Parser layer | Create `ThisResolver` |
| `MethodScopeContext` and sibling scopes don't have `ThisResolver` | All scope contexts | Add `ThisResolver` to each |
| `ReturnContext` doesn't allow `this` | `ReturnContext.php` | Add `ThisResolver` |
| `ArrowFunctionDeclarationContext` doesn't allow `this` | `ArrowFunctionDeclarationContext.php` | Add `ThisResolver` |
| `ReturnTypeContext` only accepts primitives/supertypes/metatypes — not `Self` | `ReturnTypeContext.php` + `TypeResolver.php` | Extend to accept `Self` keyword |
| `ReturnTypeEmitter` lowercases all types — `Self` → `self` not `static` | `ReturnTypeEmitter.php` | Special-case `Self` → `static` |
| No checker to validate `this` is inside a class scope | Checker layer | Create `ThisScopeChecker` |
| No checker to validate `this.property` existence | Checker layer | Part of `ThisScopeChecker` |

## Key Architectural Insight

`this` in PHireScript is a keyword expression — it behaves like a variable reference to the current object. The pattern to follow is:

```
ReturnResolver    → token 'return'   → ReturnNode
ThisResolver      → token 'this'     → ThisExpressionNode  ← to be created
```

After `ThisResolver` registers the `ThisExpressionNode` as the virtual variable on focus, the existing chain machinery handles `this.method()` and `this.property` via `DotResolver` + `FunctionCallResolver`.

## Scope Context Inventory

All contexts that need `ThisResolver` added to their resolvers list:

| Context | File | Current state |
|---------|------|---------------|
| `MethodScopeContext` | `Scopes/MethodScopeContext.php` | Has `DotResolver`, `FunctionCallResolver` — just needs `ThisResolver` |
| `IfScopeContext` | `Scopes/IfScopeContext.php` | Same pattern |
| `ElseScopeContext` | `Scopes/ElseScopeContext.php` | Same pattern |
| `ElseIfScopeContext` | `Scopes/ElseIfScopeContext.php` | Same pattern |
| `TryScopeContext` | `Scopes/TryScopeContext.php` | Same pattern |
| `HandleScopeContext` | `Scopes/HandleScopeContext.php` | Same pattern |
| `AlwaysScopeContext` | `Scopes/AlwaysScopeContext.php` | Same pattern |
| `ReturnContext` | `Statements/ReturnContext.php` | Needs `ThisResolver` for `return this` |
| `ArrowFunctionDeclarationContext` | `Declarations/ArrowFunctionDeclarationContext.php` | Needs `ThisResolver` for arrow fn body |

## `Self` Return Type Resolution

**Finding**: `ReturnTypeContext` delegates to `TypeResolver` which checks:
- `isNull()`, `isPrimitive()`, `isSuperType()`, `isMetaType()` — all token-type checks
- `isDependencyOf(...)` — package dependency check

`Self` is a `T_KEYWORD` (from Scanner) — none of the above match. Two options:
1. Extend `TypeResolver.isTheCase()` to also match `token->value === 'Self'`
2. Create a dedicated `SelfReturnTypeResolver` just for this token in `ReturnTypeContext`

**Decision**: Option 1 is simpler and consistent — `TypeResolver` already handles all type-shaped tokens; `Self` is just one more type-shaped keyword.

**Emitter**: `ReturnTypeEmitter` calls `mb_strtolower($type)` for all types. Add a guard before the loop:
```php
if ($type === 'Self') {
    $types[] = 'static';
    continue;
}
```

## `this.property` Bare Access

**Finding**: `DotResolver` sets the last child as virtual variable on focus. After `ThisResolver` creates a `ThisExpressionNode` and it becomes the focus, a dot sets it as focus. The next token (a bare identifier, not followed by `(`) hits the resolvers in `MethodScopeContext`. 

Currently `VariableConsumptionResolver` handles identifiers that are known variables. `this.name` where `name` is not a declared variable would fall through to `FunctionCallNotFoundResolver` and throw "method does not exist".

**Resolution**: A `ThisPropertyAccessResolver` that recognizes: focus is `ThisExpressionNode` + token is identifier + next token is NOT `(`. It produces a `PropertyAccessNode` (or similar) mapping to `$this->propName`.

Verify if `PropertyAccessNode` already exists or if a new lightweight node is needed.
