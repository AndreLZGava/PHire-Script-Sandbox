# Feature Specification: Unified Expression Context

**Feature Branch**: `006-expression-context`

**Created**: 2026-06-29

**Status**: Draft

**Input**: User description: "ExpressionContext unificado com suporte a operadores aritméticos, lógicos, de comparação, negação unária e parênteses de agrupamento em qualquer contexto de expressão (return, assignment, if). Inclui `**` como exponenciação, `!x` e `-x` como negação unária, fechamento de contexto por lookahead e suporte a expressões multi-linha via profundidade de parênteses. Não inclui bitwise (v0.1 out-of-scope). Funções matemáticas via TypeMethods (Float/Int): adicionar `root(n)`, `log()`, `log(base)` ao Float e Int; adicionar `round`, `floor`, `ceil` ao Int. Renomear `ComparisonExpressionResolver` para `BinaryExpressionResolver`."

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Arithmetic in assignments (Priority: P1)

A developer writing PHireScript can use arithmetic operators (`+`, `-`, `*`, `/`, `%`, `**`) on the right-hand side of an assignment and get correct PHP output.

**Why this priority**: The most fundamental use of expressions — setting a variable to a computed value. Every other user story builds on this foundation.

**Independent Test**: Compile a `.ps` file with `result = price * 1.1`, `tax = base ** 2`, `rest = total % 3` and verify the PHP output contains `$result = $price * 1.1`, `$tax = $base ** 2`, `$rest = $total % 3`.

**Acceptance Scenarios**:

1. **Given** a simple two-operand expression `a * b`, **When** compiled, **Then** PHP output is `$a * $b` with the correct operator.
2. **Given** a chained expression `a + b * c`, **When** compiled, **Then** PHP output preserves the user-written operator sequence (PHP handles precedence).
3. **Given** exponentiaton `x ** 2`, **When** compiled, **Then** PHP output is `$x ** 2`.
4. **Given** an expression with a float literal `price * 0.5`, **When** compiled, **Then** PHP output is `$price * 0.5`.

---

### User Story 2 — Arithmetic in return statements (Priority: P1)

A developer can use arithmetic expressions inside a method's return statement.

**Why this priority**: Directly unblocks BB-2 (the blocking bug that prevents any computed return value). Required for any useful method body.

**Independent Test**: Compile a class with `# getDiscounted(): Float { return this.price * 0.9 }` and verify `return $this->price * 0.9;` appears in PHP output.

**Acceptance Scenarios**:

1. **Given** `return this.count + 1`, **When** compiled, **Then** PHP output is `return $this->count + 1;`.
2. **Given** `return n - 1` where `n` is a method parameter, **When** compiled, **Then** PHP output is `return $n - 1;`.
3. **Given** `return price * 1.1`, **When** compiled, **Then** PHP output is `return $price * 1.1;`.

---

### User Story 3 — Grouped expressions with parentheses (Priority: P1)

A developer can use parentheses to group sub-expressions, including multi-line expressions wrapped in an outer `(...)`.

**Why this priority**: Required for any non-trivial formula. Without grouping, operator precedence must be assumed from PHP, limiting expressiveness.

**Independent Test**: Compile:
```
result = (a + b) * c
tax = (
  (base * rate) + fee
)
```
and verify the PHP contains `$result = ($a + $b) * $c` and `$tax = (($base * $rate) + $fee)`.

**Acceptance Scenarios**:

1. **Given** `(a + b) * c`, **When** compiled, **Then** PHP output wraps the sub-expression: `($a + $b) * $c`.
2. **Given** a multi-line expression with outer `(` on one line and `)` on a later line, **When** compiled, **Then** the EOL inside the parens does not close the expression; the full expression is emitted correctly.
3. **Given** deeply nested parens `((a * b) + (c / d))`, **When** compiled, **Then** PHP output preserves all grouping parens.

---

### User Story 4 — Unary negation operators (Priority: P1)

A developer can negate a boolean value with `!x` and negate a numeric value with `-x`.

**Why this priority**: Foundational — absence of negation makes many conditions unwritable.

**Independent Test**: Compile `isActive = !flag` and `opposite = -count` and verify PHP output `$isActive = !$flag` and `$opposite = -$count`.

**Acceptance Scenarios**:

1. **Given** `!flag`, **When** compiled, **Then** PHP output is `!$flag`.
2. **Given** `-count`, **When** compiled, **Then** PHP output is `-$count`.
3. **Given** `!this.isAdmin()`, **When** compiled, **Then** PHP output is `!$this->isAdmin()`.
4. **Given** `-(price * 2)`, **When** compiled, **Then** PHP output is `-($price * 2)`.

---

### User Story 5 — Method calls inside expressions (Priority: P2)

A developer can mix method chain results with arithmetic operators in the same expression, e.g., `this.getCount() * 10`.

**Why this priority**: Required for any real-world formula involving object state. Depends on BB-3 (DotResolver focus propagation) being resolved.

**Independent Test**: Compile `total = this.getBase() * this.getRate()` inside a class method and verify PHP output `$total = $this->getBase() * $this->getRate()`.

**Acceptance Scenarios**:

1. **Given** `this.getCount() * 10`, **When** compiled, **Then** PHP output is `$this->getCount() * 10`.
2. **Given** `price.multipliedBy(rate) + fee`, **When** compiled, **Then** PHP output uses the TypeMethod expansion plus `+ $fee`.
3. **Given** `(this.getBase() + offset) * multiplier`, **When** compiled, **Then** grouping and chain are both preserved.

---

### User Story 6 — Math TypeMethods on Float and Int (Priority: P2)

A developer can call `.root(n)`, `.log()`, `.log(base)` on Float and Int values, and call `.round()`, `.floor()`, `.ceil()` on Int values.

**Why this priority**: These are the missing TypeMethods identified during the expression feature design. PHireScript has no naked function calls — math functions must be method calls on typed values.

**Independent Test**: Compile `r = x.root(3)`, `l = value.log()`, `lb = value.log(2)`, `n = count.round()` and verify correct PHP output (`$x ** (1.0/3)`, `\log($value)`, `\log($value, 2)`, `\round($count)`).

**Acceptance Scenarios**:

1. **Given** `x.root(n)` on a Float, **When** compiled, **Then** PHP output is `$x ** (1.0 / $n)`.
2. **Given** `x.log()` on a Float or Int, **When** compiled, **Then** PHP output is `\log($x)`.
3. **Given** `x.log(2)` on a Float or Int, **When** compiled, **Then** PHP output is `\log($x, 2)`.
4. **Given** `n.round()` on an Int, **When** compiled, **Then** PHP output is `\round($n)`.
5. **Given** `n.floor()` on an Int, **When** compiled, **Then** PHP output is `\floor($n)`.
6. **Given** `n.ceil()` on an Int, **When** compiled, **Then** PHP output is `\ceil($n)`.

---

### Edge Cases

- What happens when an expression ends with an operator and no right operand? → Compile error with clear message.
- How does a unary `-` at the start of an expression (`-x * 2`) differ from a binary `-` mid-expression? → Position-based disambiguation.
- Multi-line expression: EOL inside `(...)` must not close the context; EOL at `parenDepth == 0` must close it.
- `**` must be scanned before `*` to avoid a `*` token consuming the first character.
- `!x` where `x` is a method chain `!this.isAdmin()` must negate the entire chain result, not just `this`.
- Empty parentheses `()` are not a valid expression and must produce a compile error.
- `root(0)` — division by zero in exponent: emit as-is (`$x ** (1.0 / 0)`) and let PHP raise the error at runtime, consistent with PHireScript's "trust the developer" approach.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The compiler MUST recognise arithmetic operators `+`, `-`, `*`, `/`, `%`, `**` in any expression context (assignment RHS, return, if-condition).
- **FR-002**: The compiler MUST emit arithmetic operators as their PHP equivalents with no transformation.
- **FR-003**: The compiler MUST recognise `(` as opening an expression group, incrementing a paren-depth counter, and `)` as closing it.
- **FR-004**: The compiler MUST NOT close an expression context on EOL when paren-depth is greater than zero, enabling multi-line expressions.
- **FR-005**: The compiler MUST close an expression context on EOL when paren-depth is zero, or on `)` that brings paren-depth to zero (closing the outer group).
- **FR-006**: The compiler MUST recognise `!expr` as boolean negation and emit `!` prefixed to the PHP equivalent of `expr`.
- **FR-007**: The compiler MUST recognise `-expr` at the start of an expression or immediately after an operator as numeric negation and emit `-` prefixed to the PHP equivalent of `expr`.
- **FR-008**: The compiler MUST preserve all user-written parentheses in the PHP output, emitting `(` and `)` as-is around their grouped content.
- **FR-009**: The compiler MUST allow method chain calls (`.` and `?.`) as operands within an expression.
- **FR-010**: `ComparisonExpressionResolver` MUST be renamed to `BinaryExpressionResolver`; all call sites updated accordingly.
- **FR-011**: A shared `ExpressionContext` MUST replace the per-context resolver duplication currently present in `ReturnContext`, `AssignmentContext`, and `IfConditionContext`.
- **FR-012**: Float TypeMethods MUST include `root(n)` → `$x ** (1.0 / $n)` and `log()` / `log(base)` → `\log($x)` / `\log($x, $base)`.
- **FR-013**: Int TypeMethods MUST include `root(n)`, `log()`, `log(base)` (same as Float), and `round()` → `\round($n)`, `floor()` → `\floor($n)`, `ceil()` → `\ceil($n)`.
- **FR-014**: The `**` token MUST be scanned as a single token before `*` to prevent mis-tokenisation.
- **FR-015**: All existing sandbox cases (1–60) MUST continue to pass after this change.

### Key Entities

- **Expression**: A combination of operands (literals, variables, method chains) and operators that evaluates to a value. Can span multiple lines if wrapped in `(...)`.
- **Operand**: A literal value, variable reference, or method chain result that appears on either side of an operator.
- **Unary operator**: A prefix operator (`!`, `-`) applied to a single operand.
- **Binary operator**: An infix operator (`+`, `-`, `*`, `/`, `%`, `**`, `&&`, `||`, `==`, etc.) applied between two operands.
- **Paren group**: A sub-expression enclosed in `(...)` that is emitted with its parentheses preserved.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A developer can write `return this.price * discount` inside a class method and the compiled PHP is valid and contains the multiplication — zero workarounds needed.
- **SC-002**: A developer can write a multi-line expression wrapped in `(...)` spanning at least 3 lines and the compiler emits the correct single-statement PHP.
- **SC-003**: All 15 arithmetic scenarios covered by sandbox cases (US1–US4) compile and pass their `CaseValidation.php` assertions.
- **SC-004**: All 60 existing sandbox cases pass with no regressions after the `ExpressionContext` refactor.
- **SC-005**: A developer calling `.root(n)`, `.log()`, `.log(base)`, `.round()`, `.floor()`, `.ceil()` on Float or Int values gets valid PHP output verified by sandbox cases.
- **SC-006**: A developer can negate a boolean with `!` and a number with unary `-` and get correct PHP output in at least one sandbox case each.

## Assumptions

- Operator precedence is delegated entirely to PHP — PHireScript emits operators in the order they appear (with user parens preserved) and relies on PHP's own precedence rules. No AST-level reordering is performed.
- Bitwise operators (`&`, `|`, `^`, `~`, `<<`, `>>`) are out of scope for this feature (v0.1).
- Keyword aliases (`and`, `or`, `not`) are out of scope — only symbol operators are supported.
- User Story 5 (method calls inside expressions) depends on BB-3 (DotResolver focus propagation) being resolved first; if BB-3 is not resolved, US5 sandbox cases will be deferred.
- The `**` scanner token change is backward-compatible: no existing `.ps` syntax uses `**`.
- `root(0)` and division by zero in expressions are not caught at compile time; PHP raises the error at runtime.
- The refactor of `AssignmentContext` and `IfConditionContext` to use the shared `ExpressionContext` is in scope and does not change visible language behaviour — only the internal parser structure changes.
