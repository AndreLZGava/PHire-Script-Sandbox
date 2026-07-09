# Feature Specification: Named Parameters in Method Calls

**Feature Branch**: `010-named-params`

**Created**: 2026-07-09

**Status**: Draft

**Backlog ref**: P1-6

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Positional-only call (unchanged behaviour) (Priority: P1)

A developer calls a method using the existing positional syntax, exactly as today. The feature must not break any existing code.

**Why this priority**: Backward compatibility is non-negotiable. Any regression here breaks the entire user base.

**Independent Test**: Compile any existing `.ps` file that contains positional method calls and verify the PHP output is identical to the current output.

**Acceptance Scenarios**:

1. **Given** a variable `myVar` of type `String`, **When** the developer writes `myVar.getCsv(',', '\')`, **Then** the compiler emits valid PHP with arguments in the declared order.
2. **Given** a method with three positional params, **When** all three are supplied in order, **Then** the emitted PHP reflects that order unchanged.

---

### User Story 2 — Named-only call with reordered arguments (Priority: P1)

A developer uses named parameter syntax to pass arguments in an order that differs from the method declaration, e.g. `myVar.getCsv(enclosure: '\', separator: ',')`.

**Why this priority**: This is the core new capability. Without it the feature has no value.

**Independent Test**: Write a `.ps` file that calls a method using named params in reverse declaration order. Verify the compiled PHP emits arguments mapped to the correct positions (or uses PHP 8 named arguments syntax).

**Acceptance Scenarios**:

1. **Given** a method declared as `getCsv(separator, enclosure)`, **When** the developer writes `myVar.getCsv(enclosure: '\', separator: ',')`, **Then** the compiler resolves each argument by name and emits valid PHP regardless of the written order.
2. **Given** a named call where all required params are provided, **When** compiled, **Then** no compiler error is raised.
3. **Given** a named call that references a name that does not exist in the method signature, **When** compiled, **Then** the compiler raises a clear error identifying the unknown parameter name.

---

### User Story 3 — Mixed positional and named call is rejected (Priority: P1)

A developer mistakenly mixes positional and named arguments in the same call, e.g. `myVar.getCsv(',', enclosure: '\')`. The compiler must reject this with a descriptive error.

**Why this priority**: Allowing mixed syntax would produce silently incorrect PHP. A hard error is the only safe behaviour.

**Independent Test**: Write a `.ps` file with a mixed call and run the compiler. Verify that compilation fails with a clear error message referencing the mixed-style problem.

**Acceptance Scenarios**:

1. **Given** a call that has at least one positional and at least one named argument, **When** compiled, **Then** the compiler emits a `CompileException` with a message that identifies the call site and explains that mixing styles is not allowed.
2. **Given** a call that is entirely positional, **When** compiled, **Then** no mixing error is raised.
3. **Given** a call that is entirely named, **When** compiled, **Then** no mixing error is raised.

---

### User Story 4 — Missing required named argument is rejected (Priority: P2)

A developer uses named syntax but omits a required parameter. The compiler must catch this at compile time.

**Why this priority**: Silently emitting PHP with missing required arguments would produce a runtime error in PHP, which PHireScript's "compile-time safety" principle must prevent.

**Independent Test**: Write a `.ps` file that names only some required params. Verify a compile error identifies which required parameter is missing.

**Acceptance Scenarios**:

1. **Given** a method with two required params, **When** the developer provides only one named param, **Then** the compiler raises a clear error naming the missing parameter.
2. **Given** a method with one required and one optional param, **When** the developer names only the required param, **Then** compilation succeeds and the optional param receives its default value.

---

### Edge Cases

- What happens when the same parameter name is provided twice in a named call? → Compiler error: duplicate named argument.
- What happens when an optional parameter is omitted from a named call? → Allowed; the declared default value is used.
- What happens when a method has zero parameters and the developer writes `method()` with no args? → No change; existing behaviour preserved.
- What happens when a method is called with named params but the runtime definition (`BaseParams`) does not define names for its params? → Compiler error at bind time: param names not available for this method.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The compiler MUST accept method calls where all arguments are passed by position (existing behaviour, unchanged).
- **FR-002**: The compiler MUST accept method calls where all arguments are passed by name using the syntax `paramName: value`.
- **FR-003**: When named syntax is used, the compiler MUST resolve each argument to its declared position by matching `paramName` against the method's `BaseParams` name declarations.
- **FR-004**: The compiler MUST reject any call that mixes positional and named arguments, emitting a `CompileException` that identifies the call site and the rule violated.
- **FR-005**: The compiler MUST reject any named call that references a parameter name not present in the method's declared signature, emitting a `CompileException` naming the unknown identifier.
- **FR-006**: The compiler MUST reject any named call where a required parameter (no default value) is not supplied, emitting a `CompileException` naming the missing parameter.
- **FR-007**: The compiler MUST reject any named call where the same parameter name appears more than once, emitting a `CompileException` identifying the duplicate.
- **FR-008**: Optional parameters omitted from a named call MUST receive their declared default value in the emitted PHP.
- **FR-009**: The scanner MUST NOT introduce a new token for `:` in this context — detection of named syntax MUST be done at parse or resolution time by observing the `identifier :` pattern inside a parameter consumption context.
- **FR-010**: The checker MUST validate named argument completeness and correctness for every method call that uses named syntax, so that errors are caught regardless of which context the call appears in.

### Key Entities

- **NamedArgNode**: Represents a single named argument — holds the parameter name (string) and the value node. Created during param consumption when `identifier :` pattern is detected.
- **BaseParams** (existing): The runtime definition of a method parameter — already holds a `name` field used as the key for named resolution.
- **ParamsConsumptionContext** (existing, modified): The context that parses argument lists; must be extended to recognise and produce `NamedArgNode` instances.
- **FunctionEmitter** (existing, modified): Responsible for ordering and substituting named arguments before emission.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: All existing sandbox cases that use positional method calls compile without any change to their output.
- **SC-002**: A new sandbox case using named parameters in reverse declaration order compiles and its PHP output matches the expected positional-order emission.
- **SC-003**: All three error conditions (mixed style, unknown name, missing required param) produce a `CompileException` with a message that includes the offending call site (file, line, column).
- **SC-004**: Zero regressions across the full `php bin/stretch --mode=success` run after the feature lands.

## Assumptions

- Named argument syntax uses a single colon immediately after the identifier with no space required before it: `paramName: value`. Spaces around `:` are allowed by the scanner's whitespace handling.
- The feature targets method calls on typed variables (chain calls). Standalone function calls (`myFunction(name: value)`) are out of scope for this spec.
- PHP emission strategy: the compiler resolves named arguments to their positional slots and emits standard positional PHP (`$foo->getCsv(',', '\\')`) rather than emitting PHP 8 named argument syntax (`getCsv(separator: ',', enclosure: '\\')`). This keeps the output compatible with PHP 7.4+.
- `BaseParams::name` already holds the canonical parameter name (confirmed from code review). No runtime changes are needed.
- The feature applies to all method call contexts where `ParamsConsumptionContext` is used (chained calls, assignment RHS, return expressions). No context-specific carve-outs.
