# Implementation Plan: this Keyword and Self Return Type

**Branch**: `004-this-keyword-self-return` | **Date**: 2026-06-04 | **Spec**: [spec.md](spec.md)

## Summary

Add `this` keyword support inside class/type/immutable method bodies (including if/else/elseif, try/handle/always, and arrow functions at any nesting depth), `return this` support in methods, and `Self` as a return type annotation that maps to PHP's `: static`. The infrastructure is already present: `ThisExpressionNode` and `ThisExpressionEmitter` exist; the missing piece is a `ThisResolver` (Parser) and `ThisChecker` (scope guard), plus `Self` handling in `ReturnTypeContext` and `ReturnTypeEmitter`.

## Technical Context

**Language/Version**: PHP 8.2+ (compiler written in PHP 8.2; output targets PHP 8.2+)

**Primary Dependencies**: `nikic/php-parser` (PhpFileGenerator), PHPUnit (tests), PHPStan level 9

**Storage**: N/A

**Testing**: PHPUnit via sandbox orchestrator (`php bin/stretch --mode=success` / `--mode=error`)

**Target Platform**: CLI compiler running on Linux/macOS PHP 8.2+

**Project Type**: Transpiler/compiler (PHireScript → PHP)

**Performance Goals**: N/A for this feature

**Constraints**: PHPStan level 9 must pass; no suppressions. Token advance rule: only `Parser.php` may call `$tokenManager->advance()` — Resolvers/Contexts use read-only methods only.

**Scale/Scope**: ~7 compiler files touched; ~4–6 new sandbox cases

## Constitution Check

The constitution file is a template (not filled). Applying PHireScript-specific quality gates from `CLAUDE.md`:

| Gate | Status |
|------|--------|
| Token advance rule: only `Parser.php` advances cursor | ✅ Will comply — `ThisResolver` uses only `isTheCase` / `resolve`, no advance calls |
| PHPStan level 9 | ✅ All new classes must be fully typed |
| One commit per complete feature (sandbox case passing) | ✅ Will commit after orchestrator passes |
| No `Co-Authored-By` trailer | ✅ Noted |
| Sandbox case required for functional features | ✅ Multiple cases planned |

## Project Structure

### Documentation (this feature)

```text
specs/004-this-keyword-self-return/
├── plan.md              ← this file
├── research.md          ← Phase 0 output
├── data-model.md        ← Phase 1 output
└── tasks.md             ← Phase 2 output (/speckit-tasks)
```

### Source Code (compiler — phirescript/)

```text
phirescript/src/Compiler/Parser/Ast/Resolver/Expressions/
└── ThisResolver.php                          [NEW] — produces ThisExpressionNode for 'this' token

phirescript/src/Compiler/Checker/Expression/
└── ThisScopeChecker.php                      [NEW] — validates 'this' is inside ClassContext/TypeContext/ImmutableContext

phirescript/src/Compiler/Parser/Ast/Context/Scopes/
├── MethodScopeContext.php                    [MODIFY] — add ThisResolver to resolvers list
├── IfScopeContext.php                        [MODIFY] — add ThisResolver to resolvers list
├── ElseScopeContext.php                      [MODIFY] — add ThisResolver to resolvers list
├── ElseIfScopeContext.php                    [MODIFY] — add ThisResolver to resolvers list
├── TryScopeContext.php                       [MODIFY] — add ThisResolver to resolvers list
├── HandleScopeContext.php                    [MODIFY] — add ThisResolver to resolvers list
└── AlwaysScopeContext.php                    [MODIFY] — add ThisResolver to resolvers list

phirescript/src/Compiler/Parser/Ast/Context/Statements/
└── ReturnContext.php                         [MODIFY] — add ThisResolver so 'return this' works

phirescript/src/Compiler/Parser/Ast/Context/Declarations/
└── ArrowFunctionDeclarationContext.php       [MODIFY] — add ThisResolver for arrow functions inside methods

phirescript/src/Compiler/Parser/Ast/Context/Signatures/
└── ReturnTypeContext.php                     [MODIFY] — recognize 'Self' keyword as valid return type token

phirescript/src/Compiler/Emitter/OOP/
└── ReturnTypeEmitter.php                     [MODIFY] — map 'Self' type string to 'static' in PHP output

phirescript/src/Compiler/Emitter.php          [VERIFY] — ThisExpressionEmitter already registered at line 125
```

### Source Code (sandbox)

```text
samples/success/case_50/   [NEW] — this.property and this.method() in a plain class method
samples/success/case_51/   [NEW] — this in if/else/elseif blocks inside a method
samples/success/case_52/   [NEW] — this in try/handle/always blocks inside a method
samples/success/case_53/   [NEW] — this inside arrow function inside a method (including nested arrow)
samples/success/case_54/   [NEW] — return this with Self return type
samples/error/case_X/      [NEW] — this outside class scope → CheckerException
```

## Phase 0: Research

### Decision: ThisResolver placement

**Decision**: Add `ThisResolver` as a `ContextTokenResolver` that matches `token->value === 'this'` (it's already a `T_KEYWORD` in the Scanner) and produces a `ThisExpressionNode`. Register it in all scope contexts where `this` is valid.

**Rationale**: The scanner already emits `T_KEYWORD` for `this` (Scanner.php line 26). `ThisExpressionNode` and `ThisExpressionEmitter` already exist. The only missing link is the resolver that bridges token → node in each scope context. This follows the exact same pattern as how `ReturnResolver` bridges `return` → `ReturnNode`.

**Alternatives considered**: Adding `this` handling directly inside existing resolvers (e.g., `VariableConsumptionResolver`) — rejected because `this` is not a declared variable; it's a special keyword expression that needs its own node type.

---

### Decision: Scope validation for `this`

**Decision**: Use `$parseContext->contextManager->isIn(ClassContext::class)` (and TypeContext, ImmutableContext) inside `ThisScopeChecker` (a `Checker` with `#[CompilerPass]`) to validate that `ThisExpressionNode` only appears inside class bodies.

**Rationale**: `ContextManager::isIn()` already traverses the parent chain (line 52–62 of ContextManager.php). This is zero-overhead — no new state needed. The same mechanism is used by other checkers to determine nested context.

**Alternatives considered**: Tracking class scope via a flag in `ParseContext` — rejected because `ContextManager::isIn()` is already the canonical way to query nesting in this codebase.

---

### Decision: `this.property` access (property access via `this`)

**Decision**: When the parser sees `this` followed by `.`, the `DotResolver` sets `this` (the `ThisExpressionNode`) as the virtual variable on focus. The next token (property name or method name) is then dispatched by `FunctionCallResolver` (if followed by `(`) or by a new `ThisPropertyAccessResolver` (if it's a bare identifier access).

**Investigation finding**: The existing `PropertyAccessEmitter` and `DotResolver` already handle chaining on variables. After `ThisResolver` registers the `ThisExpressionNode` as the virtual variable focus, the existing dot-chain machinery should handle `this.method()` naturally via `FunctionCallResolver` — because the focus type will be the enclosing class type. `this.property` (bare property, no call) requires a `ThisPropertyAccessResolver` that produces a `PropertyAccessNode` for bare field reads.

**Rationale**: Reuses the existing chain machinery. Only bare property access needs new handling.

---

### Decision: `Self` return type → `: static`

**Decision**: In `ReturnTypeContext`, allow `T_KEYWORD` token with value `Self` to be accepted by the `TypeResolver`. In `ReturnTypeEmitter`, add a special case: if the type string is `Self`, emit `static` instead of `self` (lowercase).

**Rationale**: `ReturnTypeContext` currently uses `TypeResolver` which only matches primitives, supertypes, and metatypes (TypeResolver.php line 17–24). `Self` must be added as an explicit match. The emitter already does `mb_strtolower($type)` — `Self` would become `self`; we need `static` (PHP late static binding).

**Alternatives considered**: Adding `Self` as a `T_PRIMITIVE` token in the Scanner — rejected because `Self` is semantically a class-level concept, not a primitive type.

---

### Decision: Property existence validation via SymbolTable vs Reflection

**Decision**: In `ThisScopeChecker` (or a companion `ThisPropertyAccessChecker`), when a `PropertyAccessNode` rooted on `ThisExpressionNode` is found:
1. Look up the enclosing class node from the SymbolTable.
2. If the class `extends` an `ExternalNode`, use `ReflectionClass` on the PHP parent to verify the property exists.
3. Otherwise, verify the property is declared in the class body via SymbolTable.
4. Throw `CheckerException` if not found.

**Rationale**: Consistent with how `ExternalClassAccessResolver` already uses Reflection for method validation. SymbolTable already tracks class properties.

---

## Phase 1: Design

### Data Model

No new persistent data structures. The existing nodes are sufficient:

| Node | Status | Role |
|------|--------|------|
| `ThisExpressionNode` | EXISTS (`Expressions/ThisExpressionNode.php`) | AST node for `this` keyword |
| `ThisExpressionEmitter` | EXISTS (`Emitter/Expressions/ThisExpressionEmitter.php`) | Emits `$this` |
| `PropertyAccessNode` | EXISTS — verify path | Used for `this.property` bare access |
| `ReturnTypeNode` | EXISTS | Carries return type list; extended to hold `Self` |

New files:

| File | Purpose |
|------|---------|
| `ThisResolver` | Parser resolver: `token->value === 'this'` → `ThisExpressionNode`, sets it as virtual variable |
| `ThisScopeChecker` | Checker: validates `ThisExpressionNode` is inside class/type/immutable; validates property existence |

### Contracts (language behavior)

**`this` in method scope**
```
// PHireScript input
fn greet(): String {
  return this.name
}
// PHP output
public function greet(): string
{
    return $this->name;
}
```

**`this.method()` call**
```
// PHireScript input
fn chain(): Self {
  this.activate()
  return this
}
// PHP output
public function chain(): static
{
    $this->activate();
    return $this;
}
```

**`this` in if block**
```
// PHireScript input
if active == true {
  this.disable()
}
// PHP output
if ($active === true) {
    $this->disable();
}
```

**`this` outside class (error)**
```
// PHireScript input (top-level)
this.name   ← CompileException: 'this' is not valid outside a class context
```

**`Self` return type**
```
// PHireScript input
fn setName(name: String): Self { ... }
// PHP output
public function setName(string $name): static { ... }
```

### Agent Context Update

The CLAUDE.md currently points to `specs/003-method-chaining/plan.md`. It will be updated to point to `specs/004-this-keyword-self-return/plan.md` after this plan is finalized.
