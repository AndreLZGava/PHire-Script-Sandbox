# Implementation Plan: Unified Expression Context

**Branch**: `006-expression-context` | **Date**: 2026-06-29 | **Spec**: [spec.md](spec.md)

## Summary

Implement a unified `ExpressionContext` that handles arithmetic operators (`+`, `-`, `*`, `/`, `%`, `**`), unary negation (`!`, `-`), parenthesised grouping (including multi-line), and method chain operands — replacing the duplicated per-context resolver lists currently scattered across `ReturnContext`, `AssignmentContext`, `IfConditionContext`, and 9+ scope contexts. Also: add `**` to the Scanner, rename `ComparisonExpressionResolver` to `BinaryExpressionResolver`, add missing math TypeMethods to `FloatMethods` and `IntMethods`.

## Technical Context

**Language/Version**: PHP 8.2 (compiler source), PHireScript (language being compiled)

**Primary Dependencies**: `phirescript/` compiler pipeline — Scanner, Parser, Emitter. No new external dependencies.

**Storage**: N/A — compiler operates in memory; outputs `.php` files.

**Testing**: PHPUnit (sandbox `CaseValidation.php`), `composer quality` inside `phirescript/`.

**Target Platform**: Linux/macOS, CLI PHP 8.2+.

**Project Type**: Compiler/transpiler — changes affect the parsing and emission pipeline.

**Performance Goals**: No measurable regression on existing 60 sandbox cases.

**Constraints**: Token advance rule — only `Parser.php:65` calls `$tokenManager->advance()`. Resolvers/Contexts use read-only token methods only.

**Scale/Scope**: Changes touch Scanner, `ComparisonExpressionResolver` (rename + 23 call sites), `ExpressionContext` (currently empty skeleton), `AssignmentContext`, `ReturnContext`, `IfConditionContext` delegation, `FloatMethods`, `IntMethods`. Blast radius: medium.

## Constitution Check

The project has no filled constitution. PHireScript-specific architectural gates from `CLAUDE.md` apply:

- [x] **Token advance rule** — no Resolver or Context will call `advance()` directly; `ExpressionContext.canClose()` uses lookahead only
- [x] **Trinity completeness** — no new Node/Context/Resolver trinity needed; reusing `BinaryExpressionNode` + skeleton `ExpressionContext`
- [x] **Binder ordering** — no new Binders introduced
- [x] **Emitter registration** — `BinaryExpressionEmitter` already registered; no new emitter needed
- [x] **Critical area blast radius** — Scanner gets one new token pattern; `ComparisonExpressionResolver` renamed (23 sites, mechanical); `ExpressionContext` filled from scratch; three contexts delegate to it

## Project Structure

### Documentation (this feature)

```text
specs/006-expression-context/
├── plan.md              ← this file
├── research.md          ← phase 0 output
├── data-model.md        ← phase 1 output
└── tasks.md             ← phase 2 output (/speckit-tasks)
```

### Source Code — files touched

```text
phirescript/src/
├── Compiler/
│   ├── Scanner.php                                       ← add ** to T_MODIFIER before T_SYMBOL
│   ├── Parser/
│   │   ├── Ast/
│   │   │   ├── Resolver/
│   │   │   │   └── Expressions/
│   │   │   │       └── ComparisonExpressionResolver.php  ← RENAME to BinaryExpressionResolver + add arithmetic ops
│   │   │   │       └── BinaryExpressionResolver.php      ← (the renamed file)
│   │   │   └── Context/
│   │   │       ├── Expressions/
│   │   │       │   ├── ExpressionContext.php             ← FILL: the new unified context
│   │   │       │   ├── AssignmentContext.php             ← delegate arithmetic to ExpressionContext
│   │   │       │   └── BinaryExpressionContext.php       ← add arithmetic ops + `**`
│   │   │       └── Statements/
│   │   │           └── ReturnContext.php                  ← delegate arithmetic to ExpressionContext
│   │   │       └── Scopes/
│   │   │           └── IfConditionContext.php            ← add BinaryExpressionResolver (rename only)
├── Emitter/
│   └── (no changes — BinaryExpressionEmitter already works)
└── Runtime/
    └── DefaultOverrideMethods/
        └── Types/
            ├── FloatMethods.php                          ← add root(n), log(), log(base)
            └── IntMethods.php                            ← add root(n), log(), log(base), round(), floor(), ceil()
samples/
└── success/
    ├── case_61/                                          ← arithmetic in assignments + return
    ├── case_62/                                          ← parenthesised and multi-line expressions
    ├── case_63/                                          ← unary negation !x and -x
    ├── case_64/                                          ← method calls as operands (depends on BB-3)
    └── case_65/                                          ← new math TypeMethods
```

**All 23 `ComparisonExpressionResolver` call sites** (contexts that `use` or `new` it):
`AssignmentContext`, `BinaryExpressionContext`, `ElseScopeContext`, `TryScopeContext`, `AlwaysScopeContext`, `ElseIfScopeContext`, `IfConditionContext`, `HandleScopeContext`, `ReturnContext`, `MethodScopeContext`, `IfScopeContext` — mechanical find-and-replace in imports and constructor arrays.

## Implementation Architecture

### How `**` tokenisation works

The Scanner tests patterns in order. `T_MODIFIER` currently matches `++`, `--`, `===`, `!==`, `==`, `!=`, `<=`, `>=`, `&&`, `||`. Add `\*\*` to `T_MODIFIER` **before** the `T_SYMBOL` pattern (which matches `*` as a single character). This ensures `**` is consumed as one token, not two `*` tokens.

```php
// Scanner.php T_MODIFIER pattern — add \*\* to the group
'T_MODIFIER' => '/^(\*\*|\->|=>|::|\.\.\.|\+\+|--|===|!==|==|!=|<=|>=|&&|\|\|)/',
```

### What `BinaryExpressionResolver` (renamed) does

Current `ComparisonExpressionResolver` recognises `['>', '<', '==', '===', '!=', '!==', '>=', '<=']`. After rename and extension, it recognises all binary operators:

```php
private const OPERATORS = [
    // arithmetic
    '+', '-', '*', '/', '%', '**',
    // comparison
    '>', '<', '==', '===', '!=', '!==', '>=', '<=',
    // logical
    '&&', '||',
];
```

`isTheCase()` — same guard: token value in OPERATORS and (`peekPrevious() !== null` OR `!empty($context->children)`).

`resolve()` — identical: pop left operand, create `BinaryExpressionNode($left, $op, null)`, enter `BinaryExpressionContext`.

### What `ExpressionContext` does

`ExpressionContext` is the **new unified expression host**. It replaces the duplicated resolver lists in `ReturnContext` and `AssignmentContext` for the RHS expression portion.

**Resolver set** (complete):

```php
$this->resolvers = [
    // value producers
    new NullLiteralResolver(),
    new StringLiteralResolver(),
    new NumberLiteralResolver(),
    new BoolLiteralResolver(),
    new ArrayLiteralResolver(),
    new ObjectLiteralResolver(),
    new PrimitiveCastingResolver(),
    new ArrayResolver(),
    new QueueResolver(),
    new StackResolver(),
    new MapResolver(),
    new ListResolver(),
    new GlobalConstantResolver(),

    // variable and this
    new VariableReferenceResolver(),
    new ThisResolver(),
    new ThisPropertyAccessResolver(),

    // method chains
    new FunctionCallResolver(),
    new FunctionCallNotFoundResolver(),
    new DotResolver(),
    new SafeNavigationResolver(),

    // binary / comparison
    new BinaryExpressionResolver(),  // renamed from ComparisonExpressionResolver

    // unary negation (NEW)
    new UnaryNegationResolver(),

    // paren group (NEW)
    new ParenGroupResolver(),

    // casting
    new SuperTypeCastingResolver(),
    new MetaTypeCastingResolver(),
    new TypeResolver(),
    new PrimitiveResolver(),

    // structural
    new AssignmentResolver(),
    new VariableConsumptionResolver(),
    new CommentResolver(),
    new EndOfLineResolver(),
];
```

**`canClose()` — paren-depth aware**:

```php
public function canClose(Token $token, ParseContext $parseContext): bool
{
    if ($this->parenDepth > 0) {
        // inside parens: close only on ) that brings depth to 0
        if ($token->isClosingParenthesis()) {
            $this->parenDepth--;
            return $this->parenDepth === 0;
        }
        return false;  // EOL inside multi-line expression — do not close
    }
    // at root level: EOL or comment closes
    if ($token->isEndOfLine() || $token->isComment()) {
        $next = $parseContext->tokenManager->getNextTokenAfterCurrent();
        if ($next->isDot() || $next->isSafeNavigation()) {
            return false;  // multi-line chain continues
        }
        return true;
    }
    return false;
}
```

`$this->parenDepth` is incremented by `ParenGroupResolver` when `(` is encountered, and decremented in `canClose()` when `)` is encountered.

**`afterClose()`**: adds `$this->node` (or the last child) to the parent context, matching existing `BinaryExpressionContext.afterClose()` pattern.

### New resolvers: `UnaryNegationResolver` and `ParenGroupResolver`

**`UnaryNegationResolver`**:

```
isTheCase: token is `!` or `-`
           AND (context.children is empty OR previous token was an operator)
resolve:   read next token via peekNext(), emit as UnaryExpressionNode(op, null)
           enter ExpressionContext to fill the operand
```

This requires a `UnaryExpressionNode`:

```php
class UnaryExpressionNode extends Expression {
    public function __construct(
        Token $token,
        public string $operator,  // '!' or '-'
        public ?Node $operand = null
    ) {}
}
```

And `UnaryExpressionEmitter`:

```php
public function emit(object $node, EmitContext $ctx): string {
    $operand = $ctx->emitter->emit($node->operand, $ctx);
    return "{$node->operator}{$operand}";
}
```

**`ParenGroupResolver`**:

```
isTheCase: token is `(`
resolve:   increment $context->parenDepth; emit `(` literal into output via a ParenNode
           (OR: simply add a StringLiteralNode('(') as a passthrough and let the emitter concatenate)
```

Simpler approach: instead of a dedicated node, `ExpressionContext` tracks `$parenDepth` and emits parens directly as string fragments. The final emitter for `ExpressionContext` concatenates all children (nodes + paren strings).

### How `ReturnContext` changes

`ReturnContext` no longer manages its own resolver list for expressions. After recognising the `return` keyword (already done in `ReturnResolver`), the `ReturnContext` enters an `ExpressionContext`. When `ExpressionContext` closes, `ReturnContext.handleReturn()` receives the result node as the return expression.

Concretely: `ReturnContext` keeps only structural resolvers (`EndOfLineResolver`, `CommentResolver`) and delegates everything else to `ExpressionContext` via the context manager.

### How `AssignmentContext` changes

Same pattern: after the `=` is consumed, `AssignmentContext` enters an `ExpressionContext` for the RHS. `ExpressionContext.afterClose()` hands the result node back to `AssignmentContext`, which sets `node->right`.

### `BinaryExpressionContext` changes

Add `**` and arithmetic operators to `LOGICAL_OPERATORS` / `COMPARISON_OPERATORS` constants (or rename to `BINARY_OPERATORS`). Update `canClose()` to also not close when the next token is an arithmetic operator.

### Math TypeMethods to add

**FloatMethods** — new methods:

| Method | PHP output | Return |
|--------|-----------|--------|
| `root(n)` | `@self ** (1.0 / @n)` | Float |
| `log()` | `\log(@self)` | Float |
| `log(base)` | `\log(@self, @base)` | Float |

**IntMethods** — new methods:

| Method | PHP output | Return |
|--------|-----------|--------|
| `root(n)` | `@self ** (1.0 / @n)` | Float |
| `log()` | `\log(@self)` | Float |
| `log(base)` | `\log(@self, @base)` | Float |
| `round()` | `\round(@self)` | Int |
| `floor()` | `\floor(@self)` | Int |
| `ceil()` | `\ceil(@self)` | Int |

## Complexity Tracking

No constitution violations. The `ExpressionContext` skeleton (`ExpressionContext.php`) already exists in the codebase — this feature fills it. `BinaryOperationContext.php` also exists as an empty skeleton and is left untouched (not used by this feature).
