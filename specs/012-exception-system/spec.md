# Feature Specification: PHireScript Exception System V1

**Feature Branch**: `012-exception-system`

**Created**: 2026-07-22

**Status**: Draft

**Input**: User description: "Implement a modern exception system for PHireScript that maintains runtime compatibility with PHP while providing a cleaner syntax, stronger typing, immutability, checked exceptions, and improved developer ergonomics."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Declare a Custom Exception Type (Priority: P1)

A developer declares a new exception type using the `exception` keyword, with or without typed properties.

**Why this priority**: This is the foundational building block — every other exception feature depends on it.

**Independent Test**: Can be fully tested by writing an `exception` declaration and verifying the compiler emits a valid PHP class extending `Exception`.

**Acceptance Scenarios**:

1. **Given** a `.phs` file with `exception ValidationException`, **When** compiled, **Then** the output is `class ValidationException extends Exception {}`.
2. **Given** `exception InvalidEmailException extends ValidationException`, **When** compiled, **Then** the output is `class InvalidEmailException extends ValidationException {}`.
3. **Given** an exception with typed properties (`String field`, `String reason`), **When** compiled, **Then** a `public readonly` constructor is generated automatically.
4. **Given** an exception with both properties and a custom `constructor`, **When** compiled, **Then** no auto-generated constructor is emitted.

---

### User Story 2 - Throw an Exception (Priority: P1)

A developer throws an exception without the `new` keyword.

**Why this priority**: Throw is the primary runtime interaction with the exception system and must work correctly for the feature to be usable at all.

**Independent Test**: Can be fully tested by compiling a `throw ExceptionType(...)` statement and verifying `throw new ExceptionType(...)` is emitted in PHP.

**Acceptance Scenarios**:

1. **Given** `throw ValidationException(field: 'email')`, **When** compiled, **Then** the output is `throw new ValidationException(field: 'email');`.
2. **Given** `throw ValidationException(message: 'Explicit message')`, **When** compiled, **Then** the explicit message is passed to the constructor.
3. **Given** an exception declaration with a `message: 'Template {field}'` and a throw with `field: 'email'`, **When** the exception is thrown, **Then** the message resolves to `'Template email'`.
4. **Given** `throw ValidationException(cause: e)`, **When** compiled, **Then** the output maps `cause:` to PHP's `previous:` parameter.
5. **Given** `throw ValidationException(context: { userId: user.id })`, **When** compiled, **Then** a context object is passed to the constructor.

---

### User Story 3 - Catch Exceptions with `handle` (Priority: P1)

A developer uses `try / handle / always` to catch and handle exceptions.

**Why this priority**: Exception catching is as fundamental as throwing.

**Independent Test**: Can be fully tested by compiling a `try/handle/always` block and verifying the PHP `try/catch/finally` output.

**Acceptance Scenarios**:

1. **Given** a `try { } handle ValidationException e { }` block, **When** compiled, **Then** a `try { } catch (ValidationException $e) { }` block is emitted.
2. **Given** multiple `handle` clauses, **When** compiled, **Then** multiple `catch` blocks are emitted in order.
3. **Given** `handle ValidationException | DatabaseException e { }`, **When** compiled, **Then** a union catch `catch (ValidationException | DatabaseException $e)` is emitted.
4. **Given** `always { }`, **When** compiled, **Then** a `finally { }` block is emitted.
5. **Given** an exception hierarchy where `InvalidEmailException extends ValidationException`, **When** catching `ValidationException`, **Then** it matches `InvalidEmailException` at runtime.

---

### User Story 4 - Checked Exceptions with `throws` (Priority: P2)

A developer declares which exceptions a function may throw; the compiler enforces callers must handle or propagate them.

**Why this priority**: Checked exceptions add safety but are independent of basic throw/catch functionality.

**Independent Test**: Can be tested by writing a function with `throws`, calling it without handling, and verifying a compile error is emitted.

**Acceptance Scenarios**:

1. **Given** a function declared with `throws UserNotFoundException`, **When** a caller invokes it without a `handle` or `throws` declaration, **Then** the compiler emits an error.
2. **Given** a caller that wraps the call in `try { } handle UserNotFoundException e { }`, **When** compiled, **Then** no error is emitted.
3. **Given** a caller that re-declares `throws UserNotFoundException` in its own signature, **When** compiled, **Then** no error is emitted (propagation path is valid).
4. **Given** `throws ValidationException | DatabaseException`, **When** the caller handles only `ValidationException`, **Then** the compiler emits an error for the unhandled `DatabaseException`.

---

### User Story 5 - Immutability Enforcement (Priority: P2)

The compiler prevents mutation of exception properties after construction.

**Why this priority**: Immutability is a design invariant; it prevents a class of bugs and must be enforced at compile time.

**Independent Test**: Can be tested by writing an assignment to an exception property and verifying a compile error is emitted.

**Acceptance Scenarios**:

1. **Given** `exception ValidationException { String field }` and code `e.field = 'password'`, **When** compiled, **Then** the compiler emits an error about immutability violation.
2. **Given** a `throw ValidationException(field: 'email')` statement, **When** compiled, **Then** the generated PHP uses `readonly` properties.

---

### User Story 6 - Restrict Exception Instantiation to `throw` (Priority: P3)

The compiler prevents exception objects from being created outside of a `throw` statement.

**Why this priority**: This is a design constraint that can be deferred after core throw/catch works.

**Independent Test**: Can be tested by assigning an exception construction to a variable and verifying a compile error.

**Acceptance Scenarios**:

1. **Given** `error = ValidationException(field: 'email')`, **When** compiled, **Then** the compiler emits an error.
2. **Given** `return ValidationException(field: 'email')`, **When** compiled, **Then** the compiler emits an error.

---

### Edge Cases

- What happens when an `exception` with properties is thrown without providing all required constructor arguments?
- How does the compiler handle a `throws` declaration referencing an undeclared exception type?
- What happens when a `handle` clause references an exception type not in scope?
- What if both a `message:` template and an explicit `message:` are provided in the same `throw`? (Explicit wins.)
- What if `cause:` is provided but does not resolve to a `Throwable`? Compile error expected.
- What if a `context:` value is mutable? Compiler must enforce immutability.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The compiler MUST accept the `exception` keyword to declare exception types.
- **FR-002**: An `exception` declaration without a body MUST compile to a PHP class extending `Exception` with no additional members.
- **FR-003**: An `exception` declaration with typed properties MUST compile to a PHP class with `public readonly` promoted constructor parameters.
- **FR-004**: If no `constructor` block is declared, the compiler MUST auto-generate a constructor from the declared properties.
- **FR-005**: If a `constructor` block is declared, the compiler MUST NOT generate an automatic constructor.
- **FR-006**: Exception inheritance MUST be expressed with the `extends` keyword and compile to PHP class inheritance.
- **FR-007**: The `throw` keyword MUST accept an exception call expression without `new`; the compiler MUST emit `throw new` in PHP.
- **FR-008**: Named parameters in `throw` expressions MUST be passed through as PHP named arguments.
- **FR-009**: The `message:` named parameter in a `throw` expression MUST map to the PHP `Exception` `message` constructor argument.
- **FR-010**: A `message:` template declared on the exception type MUST be interpolated at compile time — the compiler generates a PHP string expression (e.g., `sprintf` or string concatenation) inside the constructor body using the declared property values; no runtime helper is required.
- **FR-011**: The `cause:` named parameter MUST map to PHP's `previous:` constructor argument.
- **FR-012**: The `context:` named parameter MUST be accepted and stored as a `public readonly array $context` promoted constructor parameter on the exception class; no schema is enforced on its keys or values.
- **FR-013**: All exception properties MUST be immutable; the compiler MUST emit an error on any post-construction property assignment.
- **FR-014**: Exception instantiation outside of a `throw` statement MUST produce a compile-time error.
- **FR-015**: `try / handle / always` blocks MUST compile to PHP `try / catch / finally`.
- **FR-016**: Multiple `handle` clauses MUST compile to multiple `catch` blocks in declaration order.
- **FR-017**: Union `handle` types (`A | B`) MUST compile to PHP union catch syntax.
- **FR-018**: Functions MAY declare `throws ExceptionType` (or union `throws A | B`) in their signature.
- **FR-019**: The compiler MUST enforce checked exception handling at all call sites — top-level functions, instance method calls, and static method calls — requiring callers to either handle the exception or propagate it via their own `throws` declaration.
- **FR-020**: The compiler MUST emit an error if a `throws` declaration references a type that does not resolve to a declared exception.
- **FR-021**: The standard PHP exception API (`getMessage()`, `getCode()`, `getPrevious()`, `getTrace()`, `getTraceAsString()`) MUST remain accessible on exception instances.
- **FR-022**: The `error` keyword for declaring custom error types MUST NOT be supported; the compiler MUST emit a helpful error if encountered.
- **FR-023**: The `code:` named parameter in `throw` expressions MUST map to PHP's `code` constructor argument.

### Key Entities

- **ExceptionDeclaration**: A named exception type, optional parent, optional properties, optional constructor, optional message template.
- **ExceptionProperty**: A typed, immutable field on an exception type.
- **ThrowStatement**: A `throw ExceptionType(...)` expression; compiles to `throw new ExceptionType(...)`.
- **HandleClause**: A `handle ExceptionType varName { }` block within a try/handle/always construct.
- **ThrowsAnnotation**: A `throws ExceptionType | ...` signature modifier on a function or method declaration.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: All exception declaration forms (bare, with properties, with inheritance, with custom constructor) compile without errors.
- **SC-002**: All throw forms (explicit message, template message, cause, context, code) compile to correct PHP output.
- **SC-003**: Immutability violations are detected at compile time — zero false negatives on direct property assignment.
- **SC-004**: Checked exception violations (unhandled `throws`) are detected at compile time — zero false negatives for direct callers.
- **SC-005**: `try / handle / always` constructs compile to semantically equivalent PHP `try / catch / finally` blocks.
- **SC-006**: Exception instantiation outside `throw` is rejected at compile time.
- **SC-007**: All sandbox test cases for the exception system pass via `php bin/stretch --mode=success`.

## Clarifications

### Session 2026-07-22

- Q: Where does message template interpolation happen — compile time or runtime? → A: Compile time. The compiler generates a PHP string expression (e.g., `sprintf`) inside the constructor body; no runtime helper is needed.
- Q: Does checked exception enforcement apply to all call sites or only to functions/static calls in V1? → A: All call sites — top-level functions, instance methods, and static methods are all enforced.
- Q: How is the `context:` value represented in generated PHP? → A: Plain `readonly array` — stored as a `public readonly array $context` promoted constructor parameter on the exception class.

## Assumptions

- PHP 8.1+ is the target runtime (required for `readonly` promoted constructor parameters).
- The existing `try / handle / always` parsing infrastructure is already in place; this feature extends it, not replaces it.
- Named parameter syntax in throw expressions reuses the existing named parameter infrastructure (feature 010).
- Checked exception enforcement applies only within PHireScript source files; calls to PHP-native functions are exempt from `throws` checking in V1.
- The `context:` value is stored as a plain `readonly array` on the exception; no schema is enforced on its keys or values in V1.
- Automatic message generation for exceptions without a `message:` template or explicit `message:` argument is delegated to PHP's default `Exception` behavior (empty string message).
- Exception generics, automatic throws inference, and typed Result/Failure values are explicitly out of scope for V1.
