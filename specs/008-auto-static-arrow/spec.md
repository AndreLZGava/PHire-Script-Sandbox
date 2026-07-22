# Feature Specification: Automatic `static` Inference on Arrow Functions

**Feature Branch**: `008-auto-static-arrow`

**Created**: 2026-07-09

**Status**: Draft

**Input**: User description: "a P2-30 do backlog"

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Arrow function without `this` receives `static` prefix automatically (Priority: P1)

A PHireScript developer writes an arrow function that captures external variables but never references `this`. The compiled PHP output should include the `static` keyword before `function`, which prevents PHP from binding `$this` to the closure — yielding a small but correct optimisation at no cost to the developer.

**Why this priority**: This is the core behaviour of the feature. Every arrow function that does not touch `this` should be emitted as `static function`. The developer writes no extra syntax — the compiler decides transparently.

**Independent Test**: Compile a `.phs` file with one arrow function whose body references an external variable but never uses `this`. Verify the emitted PHP reads `static function` at the start of the closure.

**Acceptance Scenarios**:

1. **Given** an arrow function body that references only parameters and captured variables, **When** the compiler emits it, **Then** the PHP closure begins with `static function`.
2. **Given** an arrow function with no parameters and no captured variables and no `this`, **When** the compiler emits it, **Then** the PHP closure begins with `static function`.
3. **Given** an arrow function assigned to a variable, **When** the compiler emits it without `this`, **Then** the assignment reads `$name = static function(...) ...`.

---

### User Story 2 — Arrow function that uses `this` does NOT receive `static` prefix (Priority: P1)

A developer writes an arrow function inside a class method that accesses instance properties via `this`. The compiler must detect the presence of `this` in the AST and emit a regular (non-static) closure, preserving `$this` binding in PHP.

**Why this priority**: Incorrectly marking a `$this`-using closure as `static` produces a fatal PHP runtime error. This correctness gate is as important as the optimisation itself.

**Independent Test**: Compile a `.phs` file with an arrow function whose body contains `this.someProperty`. Verify the emitted PHP reads `function(` without the `static` prefix.

**Acceptance Scenarios**:

1. **Given** an arrow function body that contains a `this` property access, **When** the compiler emits it, **Then** the PHP closure begins with `function` (no `static`).
2. **Given** an arrow function body where `this` appears only inside a nested arrow function, **When** the compiler emits the outer function, **Then** the outer closure still begins with `static function` (inner `this` does not contaminate the outer boundary).

---

### User Story 3 — No PHireScript syntax change required (Priority: P2)

A developer who has existing `.phs` files with arrow functions makes no changes to their code. After upgrading to a compiler version that includes this feature, all arrow functions that never reference `this` automatically gain `static` in the emitted PHP.

**Why this priority**: Zero developer friction is the design goal — the optimisation is invisible. This story validates backward compatibility.

**Independent Test**: Take any existing passing case that contains arrow functions without `this`, recompile it, and verify the output now contains `static function` without any `.phs` file modification.

**Acceptance Scenarios**:

1. **Given** an existing `.phs` file that compiles successfully today, **When** recompiled after this feature lands, **Then** compilation still succeeds and the output is identical except for the added `static` prefix where applicable.

---

### Edge Cases

- What happens when the arrow function body is empty (`{}`)?  → No `this` present; emit `static function`.
- What happens when an arrow function is nested inside another arrow function?  → Each function is evaluated independently; `this` in an inner function does not affect the outer one.
- What happens when `this` is referenced only in a string literal (`"this is a string"`)? → String content is not an AST node of type `ThisExpressionNode`; should have no effect — `static` prefix is still emitted.
- What happens when the arrow function body contains a call to another arrow function that internally uses `this`? → The outer closure has no direct `ThisExpressionNode`; it still receives `static`. The inner one is a separate AST sub-tree evaluated on its own.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The compiler MUST inspect the AST of each arrow function body at emit time to determine whether any `ThisExpressionNode` is present as a direct child of that function's scope.
- **FR-002**: If no `ThisExpressionNode` is found in the arrow function's own scope, the compiler MUST prefix the emitted PHP closure with `static`.
- **FR-003**: If a `ThisExpressionNode` is found anywhere in the arrow function's own scope, the compiler MUST emit the closure without the `static` prefix.
- **FR-004**: The PHireScript syntax (`.phs` source files) MUST NOT require any new keyword, annotation, or modifier — `static` inference is entirely automatic.
- **FR-005**: The change MUST be confined to the emit phase; no scanner, parser, resolver, context, or checker modifications are required or permitted.
- **FR-006**: Nested arrow functions MUST be evaluated independently: the presence of `this` inside an inner arrow function MUST NOT influence whether the outer arrow function receives `static`.

### Key Entities

- **ArrowFunctionNode**: The AST node produced by the parser for every arrow function declaration. Its `bodyCode` (a `MethodScopeNode`) is the subtree inspected at emit time.
- **ThisExpressionNode**: The AST node produced whenever `this` appears in the source. Its presence anywhere in the arrow function's direct-scope AST triggers non-static emission.
- **ArrowFunctionEmitter**: The single emit-phase class responsible for generating the PHP closure string. This is the only file that needs to change.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Every arrow function in the existing sandbox test suite that does not reference `this` in its own body emits a `static function` prefix after the change is applied.
- **SC-002**: Every arrow function that does reference `this` continues to emit without `static`, and no existing passing sandbox case regresses.
- **SC-003**: The feature requires no new sandbox cases to validate correctness beyond confirming SC-001 and SC-002 against existing and two new targeted cases (one with `this`, one without).
- **SC-004**: The implementation touches exactly one production file (`ArrowFunctionEmitter.php`); no other compiler file is modified.

## Assumptions

- Arrow functions in PHireScript always compile to PHP anonymous functions (closures); there is no other emission target.
- `ThisExpressionNode` is the canonical AST representation of any `this` usage — no aliased or alternative node type exists for the same concept.
- The existing `collectRefs` / `collectExternalRefs` traversal logic in `ArrowFunctionEmitter` serves as the reference pattern for the new `this`-detection traversal.
- Nested arrow functions produce their own `ArrowFunctionNode` subtrees; the `this`-detection walk stops at nested `ArrowFunctionNode` boundaries (does not recurse into them).
- No performance concern exists: AST traversal at emit time is already performed for the `use (...)` capture detection; the `this`-check is a comparable linear walk.
