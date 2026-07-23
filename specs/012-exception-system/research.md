# Research: PHireScript Exception System V1

**Date**: 2026-07-22 | **Feature**: [plan.md](plan.md)

## Finding 1 — Current `throw` mechanism

**Decision**: Replace legacy `FunctionCallNotFoundResolver` → `NewExceptionNode` path with a dedicated `ThrowResolver` → `ExceptionCallNode`.

**Rationale**: `NewExceptionNode` only holds `className: string` and `message: string` — it cannot carry named arguments, `cause:`, `context:`, or type references. The new `ExceptionCallNode` models the full throw-site call as a structured node.

**Files affected**:
- `phirescript/src/Compiler/Parser/Ast/Nodes/Expressions/ExceptionCallNode.php` (NEW)
- `phirescript/src/Compiler/Parser/Ast/Resolver/Statements/ThrowResolver.php` (NEW)
- `phirescript/src/Compiler/Parser/Ast/Context/Scopes/MethodScopeContext.php` (add ThrowResolver)
- `phirescript/src/Compiler/Parser/Ast/Context/Scopes/TryScopeContext.php` (add ThrowResolver)
- `phirescript/src/Compiler/Parser/Ast/Context/Root/ProgramContext.php` (add ThrowResolver for top-level throw)

**Alternatives considered**: Extending `NewExceptionNode` — rejected; a throw node that holds named args is semantically different from a simplified string-message node. Keeping both nodes in parallel — rejected; it creates two code paths for `throw` that must be kept in sync.

---

## Finding 2 — `exception` keyword dispatching

**Decision**: Add `ExceptionResolver` (token value `'exception'`) to `ProgramContext` immediately after `TraitResolver`. Pattern is identical to `ClassResolver`.

**Files affected**:
- `phirescript/src/Compiler/Parser/Ast/Resolver/Declaration/ExceptionResolver.php` (NEW)
- `phirescript/src/Compiler/Parser/Ast/Context/Root/ProgramContext.php` (register)

---

## Finding 3 — `ExceptionNode` and `ExceptionContext`

**Decision**: `ExceptionNode` extends `ComplexObjectDefinition` (same base as `ClassNode`). `ExceptionContext` mirrors `ClassContext` but with a restricted resolver set:
- `IdentifierResolver` for name
- `ExtendsResolver` for optional parent (reuse existing)
- `ExceptionBodyResolver` (new, handles property declarations, `constructor { }`, `message:` template)

`ExceptionNode` fields:
```php
public string $name;
public ?ClassExtendsNode $extends = null;
public ?string $messageTemplate = null;
public array $properties = [];          // ExceptionPropertyNode[]
public bool $hasCustomConstructor = false;
```

**Files affected**:
- `phirescript/src/Compiler/Parser/Ast/Nodes/Declarations/ExceptionNode.php` (NEW)
- `phirescript/src/Compiler/Parser/Ast/Context/Declarations/ExceptionContext.php` (NEW)

---

## Finding 4 — Message template compile-time interpolation

**Decision**: Template pattern `{propertyName}` is parsed into a `sprintf` call. Template `'Invalid field: {field}'` with property `String field` generates:

```php
if ($message === '') {
    $message = sprintf('Invalid field: %s', $field);
}
```

Property names are matched positionally to `%s` placeholders in template order. The emitter performs the substitution during code generation.

**Rationale**: Zero runtime dependency. No extra classes or helpers. Pure PHP 8.1 string output.

**Alternatives considered**: PHP string interpolation — requires `$field` in scope (it's a promoted param, available but fragile if renamed). Heredoc — unnecessary complexity.

---

## Finding 5 — `throws` keyword placement

**Decision**: `throws` appears in function/method signatures after the return type (or after `)`if no return type). A new `ThrowsResolver` (token value `'throws'`) is added to `MethodDeclarationContext` and `ArrowFunctionDeclarationContext` resolver lists, following `ReturnTypeResolver`. It opens a `ThrowsAnnotationContext` that consumes a union type list (`ExceptionType | ExceptionType`...) and stores it in `throwsTypes: array` on the function/method node.

**Files affected**:
- `phirescript/src/Compiler/Parser/Ast/Resolver/Signatures/ThrowsResolver.php` (NEW)
- `phirescript/src/Compiler/Parser/Ast/Context/Signatures/ThrowsAnnotationContext.php` (NEW)
- `phirescript/src/Compiler/Parser/Ast/Context/Declarations/MethodDeclarationContext.php` (add ThrowsResolver)
- `phirescript/src/Compiler/Parser/Ast/Context/Declarations/ArrowFunctionDeclarationContext.php` (add ThrowsResolver)
- `phirescript/src/Compiler/Parser/Ast/Nodes/OOP/MethodDeclarationNode.php` (add `throwsTypes: array`)
- `phirescript/src/Compiler/Parser/Ast/Nodes/Declarations/FunctionNode.php` (add `throwsTypes: array`)

---

## Finding 6 — Checker pass for checked exceptions

**Decision**: `ThrowsAnnotationChecker` is a new `Checker` implementation. It:
1. Iterates all function/method nodes with non-empty `throwsTypes`.
2. Registers them in a call-table indexed by qualified name.
3. Walks all call sites (function calls + method calls + static calls) in all scopes.
4. For each call, resolves the callee's `throwsTypes` from the call-table.
5. If the call site is not inside a `TryNode` that has a `HandleNode` covering all the `throwsTypes`, AND the enclosing function/method does not re-declare them in its own `throwsTypes`, emits a `CompileException`.

**Constraint**: PHP-native calls (unresolved in the global type table) are skipped silently.

**Files affected**:
- `phirescript/src/Compiler/Checker/Declaration/ThrowsAnnotationChecker.php` (NEW)
- `phirescript/src/Compiler/Checker/Checker.php` (register)

---

## Finding 7 — Scanner keyword check

**Action required**: Verify `exception` and `throws` are in the scanner keyword list. If not, add them.

```bash
grep -n "exception\|throws" phirescript/src/Compiler/Scanner.php
```

Expected: both appear as reserved keywords so the lexer classifies them as `T_KEYWORD` or equivalent, not as identifiers.

---

## Finding 8 — `handle` union types (existing support check)

**Decision**: The `HandleEmitter` already emits `catch (...)` using `ParamsListNode`. Check whether `ParamsListEmitter` supports union types (`A | B`). If not, extend `ParamsListEmitter` to join multiple type tokens with ` | `.

**Files affected** (conditional):
- `phirescript/src/Compiler/Emitter/Signatures/ParamsListEmitter.php` (MODIFY if needed)
- `phirescript/src/Compiler/Parser/Ast/Context/Scopes/HandleContext.php` (MODIFY if needed)

---

## Finding 9 — `ExceptionEmitter` output shape

For `exception ValidationException { String field; String reason }` with template `'Invalid field: {field}'`:

```php
class ValidationException extends \Exception
{
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
}
```

For `exception ValidationException` (bare):
```php
class ValidationException extends \Exception
{
}
```

**Files affected**:
- `phirescript/src/Compiler/Emitter/Declarations/ExceptionEmitter.php` (NEW)
- `phirescript/src/Compiler/Emitter.php` (register)
