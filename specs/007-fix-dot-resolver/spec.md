# Feature Specification: DotResolver Fix via onClosingToken Refactor

**Feature Branch**: `007-fix-dot-resolver`

**Created**: 2026-06-30

**Status**: Draft

**Input**: BB-3 DotResolver fix via onClosingToken refactor (TD-18)

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Method chaining in assignment context (Priority: P1)

A developer writes `myVar = someString.toUpperCase().trim()` inside a method body. Today, only the first call compiles — the chain breaks after the first `.` because the variable focus is lost in `AssignmentContext`.

**Why this priority**: Assignment is the most common real-world context for method chains. Without this, chains are only usable in `ProgramContext` (top-level loose statements), which covers almost no production usage.

**Independent Test**: Write a `.phs` class with a method that assigns the result of a two-call string chain to a local variable. Compile and verify the PHP output contains the full chain.

**Acceptance Scenarios**:

1. **Given** a method body with `result = this.label.toUpperCase().removeSpaces()`, **When** compiled, **Then** the PHP emits `$result = \mb_strtoupper($this->label, 'UTF-8')` piped through `\trim(...)` with no intermediate variable assignment spill.
2. **Given** a chain of three calls on a property, **When** compiled, **Then** all three calls appear in the PHP output in the correct order.
3. **Given** `result = x.defined?()`, **When** compiled, **Then** PHP emits `$result = isset($x);`.

---

### User Story 2 — Method chaining in return statement (Priority: P2)

A developer writes `return this.name.toUpperCase()` or `return this.items.length()`. Today this fails because after the `.`, the `ReturnContext` loses track of the focus.

**Why this priority**: Returning a transformed value is a very common pattern. One-liner methods like `getName(): String { return this.name.toUpperCase() }` should work without needing an intermediate variable.

**Independent Test**: Write a method that returns the result of a chain directly. Verify the PHP output is `return <expression>` with no extra assignment.

**Acceptance Scenarios**:

1. **Given** `return this.name.toUpperCase()`, **When** compiled, **Then** PHP emits `return \mb_strtoupper($this->name, 'UTF-8');`.
2. **Given** `return this.count.toString()`, **When** compiled, **Then** PHP emits `return (string)$this->count;`.

---

### User Story 3 — Method chaining in if-condition context (Priority: P3)

A developer writes `if (this.label.empty?())` as a condition. The condition context must resolve the chain before evaluating.

**Why this priority**: Condition chaining is less frequent than assignment/return but is expected to work given the "everything is chainable" principle.

**Independent Test**: Write a method with an `if` that uses a chain in the condition. Compile and verify the condition emits correctly.

**Acceptance Scenarios**:

1. **Given** `if (this.label.empty?())`, **When** compiled, **Then** PHP emits `if (empty($this->label))`.
2. **Given** `if (this.count.toString().length() > 3)`, **When** compiled, **Then** PHP correctly nests the chain inside the condition expression.

---

### Edge Cases

- What happens when the closing token (`.`) is processed by a resolver inside the context before `canClose()` exits? → The new `onClosingToken()` hook must prevent resolvers inside the context from running on the closing token before exit.
- What happens when `canClose()` returns `true` for a token that is NOT a `.` (e.g., EOL in `AssignmentContext`)? → `onClosingToken()` must only be called for tokens that trigger `canClose()`, not all tokens.
- What happens in a multi-line chain where EOL is a potential close but a `.` follows on the next line? → `canClose()` returns `false` for EOL when next token is `.`; this logic already exists and must be preserved.
- What happens when a context has no `onClosingToken()` override? → Base implementation is a no-op; no behavior change for existing contexts.
- What happens with deeply nested contexts (e.g., chain inside a function call argument)? → Each context exits cleanly via its own `canClose()` / `onClosingToken()` cycle; nesting is unaffected.

---

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The compiler MUST propagate the variable focus correctly after each `.` in an `AssignmentContext` so that the next call in the chain resolves against the return type of the previous call.
- **FR-002**: The compiler MUST propagate the variable focus correctly after each `.` in a `ReturnContext`.
- **FR-003**: The compiler MUST propagate the variable focus correctly after each `.` in an `IfConditionContext`.
- **FR-004**: `ContextManager` MUST call `onClosingToken()` on the current context **before** calling `exit()` and **after** confirming `canClose()` returns `true`, and MUST NOT pass the closing token through `handle()` again.
- **FR-005**: `AbstractContext` MUST expose an `onClosingToken(Token, ParseContext)` method with a default no-op implementation so existing contexts are unaffected without changes.
- **FR-006**: `DotResolver` (Statements) MUST implement the focus-propagation logic — setting `variableOnFocus` to `end($context->children)` — inside `onClosingToken()` rather than in `resolve()`, so it only runs when the `.` is the closing token of the context, not when it appears mid-expression.
- **FR-007**: All contexts that use `DotResolver` in their resolver list MUST continue to work correctly after the refactor, with no regression in existing compilation cases.
- **FR-008**: The existing `FunctionCallContext.canClose()` / `afterClose()` multi-line chain behaviour (EOL followed by `.` does not close) MUST be preserved unchanged.
- **FR-009**: The token advance rule MUST NOT be violated: only `Parser.php` may call `$tokenManager->advance()`. `onClosingToken()` is a read-only hook.

### Key Entities

- **`ContextManager`**: Orchestrates the context stack. Gains a new `onClosingToken()` call in its `handle()` method.
- **`AbstractContext`**: Base class for all contexts. Gains a default no-op `onClosingToken()`.
- **`DotResolver` (Statements)**: Currently has an empty `resolve()`. Its focus-propagation logic moves to an `onClosingToken()` override on the contexts that need it — or the resolver itself delegates based on whether the token is the context's closing token.
- **Affected contexts**: `AssignmentContext`, `ReturnContext`, `IfConditionContext`, and any other context that lists `DotResolver` and has `canClose()` returning `true` for `.`.

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: All existing sandbox success cases (case_1 through case_66) compile without regression after the refactor — zero new failures.
- **SC-002**: A new sandbox case (`case_67` or higher) validates a two-call string chain in an assignment context and passes `CaseValidation`.
- **SC-003**: A new sandbox case validates a chain in a `return` statement and passes `CaseValidation`.
- **SC-004**: `DotResolver.resolve()` in `Statements/` is no longer empty — it contains meaningful logic, OR the focus-propagation logic is correctly placed in `onClosingToken()` overrides on the affected contexts.
- **SC-005**: No new `children[0]`-style index accesses are introduced in any context that handles multi-token expressions.

---

## Assumptions

- The refactor touches `ContextManager`, `AbstractContext`, `DotResolver` (Statements), and the three affected contexts (`AssignmentContext`, `ReturnContext`, `IfConditionContext`). Other contexts (`FunctionCallContext`, `ProgramContext`) do not need changes.
- `FunctionCallContext` already handles the `.` closing token correctly via its `afterClose()` / `DotResolver` combo — this behaviour is preserved as-is.
- The fix does not require changes to the Scanner or Parser token advance logic.
- `PropertyAccessNode.resolvedType` (introduced in MB-3 fix) is already available and correctly populated — chains starting with `this.property.method()` will work without additional changes to property resolution.
- Out of scope: chains on user-defined class method return types (e.g., `builder.withName().withValue()` where `withName` returns a user class). That requires the `SymbolTable` to expose user-class methods to the Parser, which is a separate feature.
