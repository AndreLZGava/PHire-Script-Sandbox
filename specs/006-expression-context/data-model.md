# Data Model: Unified Expression Context

**Phase 1 output** | Feature: `006-expression-context`

---

## New AST Nodes

### `UnaryExpressionNode`

```php
namespace PHireScript\Compiler\Parser\Ast\Nodes\Expressions;

class UnaryExpressionNode extends Expression
{
    public function __construct(
        Token $token,
        public string $operator,   // '!' or '-'
        public ?Node $operand = null
    ) {}
}
```

**Emitted PHP**: `{operator}{operand}` — e.g., `!$flag`, `-$count`, `-($price * 2)`.

**Used by**: `UnaryNegationResolver` (creates), `UnaryExpressionEmitter` (emits).

---

## New Resolvers

### `UnaryNegationResolver`

**File**: `phirescript/src/Compiler/Parser/Ast/Resolver/Expressions/UnaryNegationResolver.php`

**`isTheCase`**:
- Token value is `!` or `-`
- AND context children are empty (start of expression) OR the last child added is an operator node (mid-expression after an operator)

**`resolve`**:
- Creates `UnaryExpressionNode($token, $token->value)`
- Enters a new `ExpressionContext` scoped to fill the operand
- On close, sets `UnaryExpressionNode->operand = result`

---

### `ParenGroupResolver`

**File**: `phirescript/src/Compiler/Parser/Ast/Resolver/Expressions/ParenGroupResolver.php`

**`isTheCase`**: Token is `(` (`$token->isOpenParen()`)

**`resolve`**:
- Increments `$context->parenDepth`
- Adds an open-paren marker to the output stream (string `'('`)

**Note**: `ExpressionContext.canClose()` handles `)` by decrementing `parenDepth` and adding `)` to the output stream.

---

## Modified Resolvers

### `BinaryExpressionResolver` (renamed from `ComparisonExpressionResolver`)

**File rename**: `ComparisonExpressionResolver.php` → `BinaryExpressionResolver.php`

**Class rename**: `ComparisonExpressionResolver` → `BinaryExpressionResolver`

**`OPERATORS` constant extended**:

```php
private const OPERATORS = [
    '+', '-', '*', '/', '%', '**',        // arithmetic (NEW)
    '>', '<', '==', '===', '!=', '!==', '>=', '<=',  // comparison (existing)
    '&&', '||',                            // logical (existing)
];
```

**Call sites updated**: 23 files — all `use` imports and `new ComparisonExpressionResolver()` instantiations.

---

## New Emitters

### `UnaryExpressionEmitter`

**File**: `phirescript/src/Compiler/Emitter/Expressions/UnaryExpressionEmitter.php`

```php
public function supports(object $node, EmitContext $ctx): bool
{
    return $node instanceof UnaryExpressionNode;
}

public function emit(object $node, EmitContext $ctx): string
{
    $operand = $ctx->emitter->emit($node->operand, $ctx);
    return "{$node->operator}{$operand}";
}
```

**Registered in**: `phirescript/src/Compiler/Emitter.php` (alongside `BinaryExpressionEmitter`).

---

## Modified Contexts

### `ExpressionContext` (filled from skeleton)

**Paren depth field**:
```php
public int $parenDepth = 0;
```

**`canClose()` logic**:
- If `parenDepth > 0` and token is `)`: decrement depth; close only if depth reaches 0
- If `parenDepth > 0` and token is EOL: do NOT close (multi-line expression)
- If `parenDepth === 0` and token is EOL or comment: close (unless next token is `.` or `?.`)
- Otherwise: do not close

**`handle()` flow**:
- Runs the full resolver list
- Arithmetic/comparison operators enter `BinaryExpressionContext` via `BinaryExpressionResolver`
- Unary operators enter a nested `ExpressionContext` via `UnaryNegationResolver`
- `(` increments `parenDepth` via `ParenGroupResolver`

**`afterClose()`**:
- Adds the resolved expression node (last child or `$this->node`) to the parent context

### `ReturnContext` (simplified)

- Removes all value-producing resolvers from its own list
- Retains only: `EndOfLineResolver`, `CommentResolver` for structural close
- On entry, delegates to `ExpressionContext` for the expression portion
- `handleReturn()` receives the result from `ExpressionContext.afterClose()`

### `AssignmentContext` (simplified)

- Removes arithmetic resolver duplication
- After `=` is consumed, enters `ExpressionContext`
- `ExpressionContext.afterClose()` sets `node->right`

### `BinaryExpressionContext` (extended)

- Add `'**'` and arithmetic operators to the `canClose()` "do not close" list (alongside existing logical/comparison operators)
- Update `LOGICAL_OPERATORS` / `COMPARISON_OPERATORS` → merge into `BINARY_OPERATORS`

---

## Scanner Change

**File**: `phirescript/src/Compiler/Scanner.php`

**Before**:
```php
'T_MODIFIER' => '/^(\->|=>|::|\.\.\.|\+\+|--|===|!==|==|!=|<=|>=|&&|\|\|)/',
```

**After**:
```php
'T_MODIFIER' => '/^(\*\*|\->|=>|::|\.\.\.|\+\+|--|===|!==|==|!=|<=|>=|&&|\|\|)/',
```

`**` is placed first so it is matched before `T_SYMBOL` can consume a single `*`.

---

## TypeMethods additions

### `FloatMethods` — new methods

| Method | `phpCodeForConversion` | Return | Params |
|--------|----------------------|--------|--------|
| `root` | `@self ** (1.0 / @n)` | Float | `@n: float, required` |
| `log` | `\log(@self)` | Float | none |
| `log` (overload) | `\log(@self, @base)` | Float | `@base: float, optional` |

Note: PHireScript `BaseMethods` does not support true method overloading. `log` with optional `@base` param covers both: `\log(@self)` when `@base` is absent, `\log(@self, @base)` when provided. Use optional param pattern already established by `round(precision)` in FloatMethods.

### `IntMethods` — new methods

| Method | `phpCodeForConversion` | Return | Params |
|--------|----------------------|--------|--------|
| `root` | `@self ** (1.0 / @n)` | Float | `@n: int\|float, required` |
| `log` | `\log(@self)` | Float | none |
| `log` (with base) | `\log(@self, @base)` | Float | `@base: float, optional` |
| `round` | `\round(@self)` | Int | none |
| `floor` | `\floor(@self)` | Int | none |
| `ceil` | `\ceil(@self)` | Int | none |

---

## Sandbox Cases

| Case | Content | Validates |
|------|---------|-----------|
| `case_61` | Arithmetic in assignments and return | US1 + US2: `+`, `-`, `*`, `/`, `%`, `**` |
| `case_62` | Parenthesised and multi-line expressions | US3: `(a+b)*c`, 3-line expression |
| `case_63` | Unary negation `!x` and `-x` | US4: boolean and numeric negation |
| `case_64` | Method calls as operands | US5: `this.getCount() * 10` (deferred if BB-3 not resolved) |
| `case_65` | New math TypeMethods | US6: `root`, `log`, `log(base)`, `round`, `floor`, `ceil` |
