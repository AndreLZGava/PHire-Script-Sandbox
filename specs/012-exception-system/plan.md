# Implementation Plan: PHireScript Exception System V1

**Branch**: `012-exception-system` | **Date**: 2026-07-22 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/012-exception-system/spec.md`

## Summary

Implement a full exception system for PHireScript: `exception` declarations, `throw ExceptionType(...)` without `new`, immutable properties, message templates (compile-time interpolated), `cause`/`context`/`code` special params, and checked exceptions via `throws` on function/method signatures. The existing `try / handle / always` parsing infrastructure is kept; only the `handle` param resolution and `throw` expression pipeline are extended to support the new features.

## Technical Context

**Language/Version**: PHP 8.1+ (compiler written in PHP; generated code targets PHP 8.1+)

**Primary Dependencies**: PHireScript compiler pipeline (Parser → Binder → Checker → Emitter); PHPUnit for sandbox tests

**Storage**: N/A

**Testing**: PHPUnit via `php bin/stretch --mode=success`; per-case `CaseValidation.php` + `*Test.php`

**Target Platform**: PHP 8.1+ runtime; compiler runs on PHP 8.0+

**Project Type**: Compiler / transpiler

**Performance Goals**: No regression on existing cases; compile-time checking must not increase single-file parse time measurably

**Constraints**: Token advance rule — only `Parser.php` (`$tokenManager->advance()`) may advance the cursor; all resolvers/contexts are read-only

**Scale/Scope**: ~25 new/modified files across compiler + ~6 sandbox cases

## Constitution Check

*GATE: No constitution file is active for this project — no gates to evaluate.*

No violations.

## Project Structure

### Documentation (this feature)

```text
specs/012-exception-system/
├── plan.md              ← this file
├── spec.md
├── research.md
├── data-model.md
├── vscode-extension.md
├── checklists/
│   └── requirements.md
└── tasks.md             ← /speckit-tasks output (not yet)
```

### Source Code

#### phirescript/ (compiler)

```text
phirescript/src/Compiler/
├── Parser/
│   ├── Ast/
│   │   ├── Nodes/
│   │   │   ├── Declarations/
│   │   │   │   └── ExceptionNode.php              [NEW] exception declaration AST node
│   │   │   ├── Statements/
│   │   │   │   └── ThrowStatementNode.php          [MODIFY] add exceptionArgs, resolved via ExceptionCallNode
│   │   │   └── Expressions/
│   │   │       ├── ExceptionCallNode.php           [NEW] throw-site call expression (named args + special params)
│   │   │       └── NewExceptionNode.php            [KEEP for legacy; superseded by ExceptionCallNode in new flow]
│   │   ├── Context/
│   │   │   ├── Declarations/
│   │   │   │   └── ExceptionContext.php            [NEW] parses exception body (properties, constructor, message:)
│   │   │   └── Scopes/
│   │   │       └── HandleContext.php               [MODIFY] support union types in param resolver
│   │   └── Resolver/
│   │       ├── Declaration/
│   │       │   └── ExceptionResolver.php           [NEW] dispatches on token 'exception'
│   │       └── Statements/
│   │           └── ThrowResolver.php               [NEW] dispatches on token 'throw', replaces legacy FunctionCall path
├── Binder/
│   ├── Root/
│   │   └── TypeRegistrationBinder.php              [MODIFY] register ExceptionNode in global type table
│   └── Declaration/
│       └── ExceptionBinder.php                     [NEW] binds exception properties
├── Checker/
│   └── Declaration/
│       ├── ExceptionImmutabilityChecker.php         [NEW] catches post-construction property assignments
│       ├── ExceptionInstantiationChecker.php        [NEW] rejects ExceptionCallNode outside ThrowStatementNode
│       └── ThrowsAnnotationChecker.php              [NEW] enforces checked exceptions at all call sites
└── Emitter/
    ├── Declarations/
    │   └── ExceptionEmitter.php                    [NEW] emits PHP class extending Exception
    └── Statements/
        └── ThrowStatementEmitter.php               [MODIFY] delegates to ExceptionCallNode emitter
```

Also register in:
- `phirescript/src/Compiler/Emitter.php` — add `ExceptionEmitter`
- `phirescript/src/Compiler/Parser/Ast/Context/Root/ProgramContext.php` — add `ExceptionResolver`
- `phirescript/src/Compiler/Parser/Ast/Context/Scopes/MethodScopeContext.php` — add `ThrowResolver`
- `phirescript/src/Compiler/Parser/Ast/Context/Scopes/TryScopeContext.php` — add `ThrowResolver`
- `phirescript/src/Compiler/Binder/Root/TypeRegistrationBinder.php` — handle `ExceptionNode`
- `phirescript/src/Compiler/Checker/Checker.php` — register new checkers

#### PHire-Script-Sandbox (sandbox cases)

```text
samples/success/
├── case_80/    exception bare declaration + inheritance
├── case_81/    exception with properties + auto-constructor (readonly)
├── case_82/    throw syntax — explicit message, cause, context, code
├── case_83/    message template (compile-time interpolation)
├── case_84/    checked exceptions + throws annotation
└── case_85/    immutability violation (error case) + exception-only instantiation restriction
```

**Structure Decision**: Compiler-only feature spanning 3 repos; sandbox provides integration validation.

---

## Phase 0: Research

### 1. Current `throw` path (legacy)

**Decision**: The existing `throw` mechanism routes through `FunctionCallResolver` / `FunctionCallNotFoundResolver` → produces a `NewExceptionNode` (with hardcoded `className: string` and `message: string` fields). The `ThrowStatementEmitter` then emits `throw {expression};` where `expression` comes from `NewExceptionEmitter` which outputs `new \ClassName("message")`.

**Problem**: This is a simplified, lossy path. It doesn't support named arguments, `cause:`, `context:`, type-aware emission, or compile-time message interpolation.

**Decision**: Replace with a dedicated `ThrowResolver` → `ExceptionCallNode` pipeline. The `ThrowStatementNode.exceptionExpression` field already exists and accepts `mixed` — it will hold the new `ExceptionCallNode`.

**Alternatives considered**: Extending `NewExceptionNode` — rejected because it would require threading named args through a node designed for string arguments.

---

### 2. `exception` keyword dispatch

**Decision**: Add `ExceptionResolver` (dispatches on `token->value === 'exception'`) to `ProgramContext` resolver list, after `TraitResolver`. `ExceptionResolver` creates `ExceptionNode` + `ExceptionContext`, then `ProgramContext::addChild()` registers it.

**Rationale**: Identical pattern to `ClassResolver`, `TraitResolver`, `InterfaceResolver`.

---

### 3. `ExceptionNode` structure

**Decision**: `ExceptionNode` extends `ComplexObjectDefinition` (same base as `ClassNode`) and holds:
- `string $name`
- `?ClassExtendsNode $extends` — reuse existing node
- `?string $messageTemplate` — raw template string, e.g. `'Invalid field: {field}'`
- `array $properties` — `PropertyDeclarationNode[]`
- `bool $hasCustomConstructor` — true if a `constructor { }` block is declared

**Rationale**: Reusing `ClassExtendsNode` avoids duplicate node types. Keeping the message template as a raw string allows the emitter to generate `sprintf`/concat PHP at emit time.

---

### 4. Compile-time message interpolation (FR-010)

**Decision**: The `ExceptionEmitter` generates a PHP constructor that uses `sprintf`:

```php
public function __construct(
    public readonly string $field,
    public readonly string $reason,
    string $message = '',
    int $code = 0,
    ?\Throwable $previous = null,
    public readonly array $context = [],
) {
    if ($message === '') {
        $message = sprintf('Invalid field: %s', $field);
    }
    parent::__construct($message, $code, $previous);
}
```

Template tokens `{propertyName}` are replaced with `%s` positional args in the order they appear in the template; the values come from the corresponding constructor params.

**Alternatives considered**: String interpolation `"Invalid field: {$field}"` — rejected because `$field` is not in scope at the call site; we need the promoted param name. `vsprintf` — rejected (extra complexity with no benefit). Named `sprintf` patterns — not in PHP; we use positional order derived from property declaration order.

---

### 5. `throws` annotation and checked exception enforcement (FR-018/019)

**Decision**: `ThrowsAnnotationChecker` runs as a post-parse Checker pass (after binding):
1. Collects all `FunctionNode` / `MethodDeclarationNode` nodes that have a `throwsTypes` array populated by a new `ThrowsResolver` (slots into `MethodDeclarationContext` and `ArrowFunctionDeclarationContext` after `ReturnTypeResolver`).
2. For each call site found in scope bodies, resolves the callee's `throwsTypes` from the global type table.
3. Walks the call-site scope: if no `TryNode` ancestor handles the type and the containing function has no matching `throwsTypes`, emits a `CompileException`.

**Rationale**: Checker pass is the correct layer; it has full program visibility after binding.

**Constraint**: PHP-native function calls are exempt (no type table entries for them).

---

### 6. Immutability checker (FR-013)

**Decision**: `ExceptionImmutabilityChecker` walks all `AssignmentNode` nodes. If the left-hand side is a `PropertyAccessNode` (dot access) where the object resolves to an exception instance, emit a compile error.

---

### 7. Exception-only instantiation restriction (FR-014)

**Decision**: `ExceptionInstantiationChecker` walks all `FunctionCallNode` / expression nodes. If the callee resolves to an `ExceptionNode` type and the parent is NOT a `ThrowStatementNode`, emit a compile error.

---

## Phase 1: Design & Contracts

### Data Model

See `data-model.md`.

### Contracts

The exception system has no external API surface (no REST endpoints, no CLI schema changes). The compiler's token contract is:

- New token keywords registered: `exception`, `throws`
- `throw` remains an existing keyword but now routes through `ThrowResolver` (no scanner change required — `throw` already lexed)
- `exception` must be added to the scanner keyword list if not already present

Check:
```bash
grep -n "exception\|throws" phirescript/src/Compiler/Scanner.php
```

### Agent context update

See CLAUDE.md update in the implementation section below.

---

## Implementation Sequence

The feature decomposes into 5 vertically independent slices, each testable via a sandbox case:

| Slice | Cases | Description |
|-------|-------|-------------|
| S1 | case_80 | `exception` bare + inheritance — Parser → Emitter (no checker) |
| S2 | case_81 | `exception` with properties + auto-constructor (readonly) |
| S3 | case_82 | `throw ExceptionType(...)` named args + cause/context/code |
| S4 | case_83 | Message template compile-time interpolation |
| S5 | case_84 | `throws` annotation + checked exception enforcement |
| S6 | case_85 | Immutability + instantiation restriction (error cases) |

S1 must precede S2–S4. S5 and S6 depend on S1 being complete. S2–S4 can proceed in parallel after S1.

---

## Complexity Tracking

No constitution violations — no tracking required.
