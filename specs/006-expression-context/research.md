# Research: Unified Expression Context

**Phase 0 output** | Feature: `006-expression-context`

---

## 1. Scanner — tokenising `**` before `*`

**Decision**: Add `\*\*` to `T_MODIFIER` as the first alternative.

**Rationale**: The Scanner tests patterns in definition order. `T_SYMBOL` matches a single `*`. By placing `\*\*` in `T_MODIFIER` (which is tested before `T_SYMBOL`), the Scanner consumes both characters as one token. This is the same pattern already used for `++` and `--` in `T_MODIFIER`.

**Alternatives considered**:
- New `T_POW` token type — unnecessary; `T_MODIFIER` already groups multi-character operator tokens.
- Post-processing two `*` tokens into one — fragile; creates lookahead complexity in the parser.

---

## 2. `ExpressionContext` vs extending `BinaryExpressionContext`

**Decision**: Fill the existing `ExpressionContext` skeleton as the unified host. Keep `BinaryExpressionContext` as the sub-context entered when a binary operator is encountered mid-expression.

**Rationale**: `ExpressionContext.php` already exists as an empty class — it was clearly intended for this purpose. `BinaryExpressionContext` serves a different role: it manages the right-hand operand *after* an operator has been seen. The two contexts have different lifecycles and `canClose()` logic.

**Alternatives considered**:
- Extending `BinaryExpressionContext` with arithmetic — conflates two different responsibilities (hosting an expression vs. completing a binary node).
- Starting fresh with a new file — wasteful given the skeleton already exists.

---

## 3. Paren-depth tracking: where does the counter live?

**Decision**: `$parenDepth` lives as an instance field on `ExpressionContext`. `ParenGroupResolver` increments it; `canClose()` decrements it when `)` is encountered.

**Rationale**: The context owns its own lifecycle. Paren depth is local to one expression and should not be visible to the parent context or the context manager.

**Alternatives considered**:
- Counter on `ParseContext` — would bleed across nested contexts incorrectly.
- Dedicated `ParenExpressionContext` per `(` — excessive nesting; makes the emitter reconstruct paren grouping from the tree rather than passing through the literal tokens.

---

## 4. Paren emission strategy: nodes vs. string fragments

**Decision**: Emit `(` and `)` as raw string fragments directly from `ExpressionContext`, not as AST nodes.

**Rationale**: PHireScript delegates precedence entirely to PHP. The parens are pass-through — the only job is to preserve them in the output. Creating an `OpenParenNode` / `CloseParenNode` pair would add AST noise with no semantic value. `BinaryExpressionEmitter` already emits `$left $op $right` as concatenated strings; the same emitter handles `ExpressionContext` output.

**Alternatives considered**:
- `GroupExpressionNode` wrapping its children — correct for an AST-rewriting compiler; overkill here since PHireScript never reorders operators.

---

## 5. Unary negation: new resolver + new node

**Decision**: Introduce `UnaryNegationResolver`, `UnaryExpressionNode`, and `UnaryExpressionEmitter`.

**Rationale**: Unary `!` and `-` are structurally different from binary operators — they have one operand, not two. Reusing `BinaryExpressionNode` with `left = null` would require every emitter to null-check. A dedicated node is cleaner and follows the pattern used for every other syntactic construct in the compiler.

**`isTheCase` guard**: token is `!` or `-` **AND** context has no children yet, OR the previous child was itself an operator node (not a value node). This disambiguates unary `-` from binary `-`.

**Alternatives considered**:
- `BinaryExpressionNode(null, '-', right)` — requires emitter to handle null left specially.
- Pre-processing via a Scanner rule — premature; context information is needed to distinguish unary from binary.

---

## 6. `ReturnContext` and `AssignmentContext` delegation strategy

**Decision**: Both contexts enter `ExpressionContext` immediately after their structural token (`return` keyword for Return; `=` token for Assignment) and let `ExpressionContext` run until it closes. On close, the result node is handed back via `afterClose()`.

**Rationale**: This is the same pattern `ComparisonExpressionResolver` / `BinaryExpressionContext` already uses — enter a sub-context, let it close, retrieve the node from `children`. No architectural novelty.

**Current `AssignmentContext` complexity**: The existing code updates `node->right = end(children)` after every resolver fires. With delegation, this is replaced by a single assignment in `afterClose()` of `ExpressionContext`. The inline-comment bug (currently being fixed on the `005` branch) is also naturally resolved by this delegation — comments never enter `ExpressionContext`.

**Alternatives considered**:
- Keep per-context resolver lists and just add arithmetic operators — solves BB-2 without the refactor, but leaves the duplication that is already causing the comment bug and will cause future similar bugs in other contexts.

---

## 7. Blast radius of renaming `ComparisonExpressionResolver`

**Finding**: 23 PHP files reference `ComparisonExpressionResolver` — 11 context files (`AssignmentContext`, `BinaryExpressionContext`, `ElseScopeContext`, `TryScopeContext`, `AlwaysScopeContext`, `ElseIfScopeContext`, `IfConditionContext`, `HandleScopeContext`, `ReturnContext`, `MethodScopeContext`, `IfScopeContext`) plus the resolver file itself.

**Decision**: Mechanical rename — update class name, filename, and all `use` / `new` statements. PHPStan level 8 (`composer quality`) will catch any missed reference. No behaviour change.

**Risk**: Low — this is a pure rename with no logic change.

---

## 8. Math TypeMethods — `root`, `log`, `floor`, `ceil`, `round` on Int

**Finding**: `IntMethods` has `sqrt()` already. `FloatMethods` has `sqrt()`, `abs()`, `round()`, `floor()`, `ceil()` already. `round(precision)` on Float takes a precision param; `round()` on Int needs no precision.

**Decision**:
- Add `root(n)`, `log()`, `log(base)` to both `FloatMethods` and `IntMethods`.
- Add `round()`, `floor()`, `ceil()` to `IntMethods` only (they already exist on Float).
- `root(n)` emits `@self ** (1.0 / @n)` — the `1.0` cast ensures float division even when `n` is an integer, giving correct fractional exponent.

**Alternatives considered**:
- `\pow(@self, 1.0 / @n)` — equivalent; `**` is preferred for consistency with the `**` operator being added.
- Separate `log2()` and `log10()` shortcuts — deferred; `log(2)` and `log(10)` cover these cases.
