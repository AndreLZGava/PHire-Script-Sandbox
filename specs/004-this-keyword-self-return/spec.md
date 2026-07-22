# Feature Specification: this Keyword and Self Return Type

**Feature Branch**: `004-this-keyword-self-return`

**Created**: 2026-06-04

**Status**: Draft

## Clarifications

### Session 2026-06-04

- Q: When `this` is used inside a nested arrow function more than one level deep inside a class method, is it still valid? → A: Valid at any depth — the scope tracker propagates class context without a nesting limit.
- Q: When `this.prop` is accessed and the property was not declared, should the checker error, warn, or allow? → A: Always throw `CheckerException` — using SymbolTable for pure PHireScript classes and Reflection for classes that extend an `external` PHP parent.

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Use `this` inside class methods (Priority: P1)

A PHireScript developer writing class methods needs to access the current object's properties and call other methods on it using the `this` keyword.

**Why this priority**: Core object-oriented pattern; without it, classes cannot reference their own state or behavior.

**Independent Test**: Can be tested by compiling a class with a method that reads `this.property` and calls `this.method()`, then verifying the generated PHP uses `$this->property` and `$this->method()`.

**Acceptance Scenarios**:

1. **Given** a class with a String property `name`, **When** a method reads `this.name`, **Then** the emitted PHP contains `$this->name`
2. **Given** a class with a method `greet()`, **When** another method calls `this.greet()`, **Then** the emitted PHP contains `$this->greet()`
3. **Given** a method body containing `this`, **When** it appears in an `if` / `else if` / `else` block, **Then** it still emits `$this->...` correctly
4. **Given** a method body containing `this`, **When** it appears in a `try` / `handle` / `always` block, **Then** it still emits `$this->...` correctly

---

### User Story 2 — `return this` and `Self` return type in class methods (Priority: P1)

A PHireScript developer wants to implement fluent/builder patterns by returning the current instance from a method typed as `Self`.

**Why this priority**: `Self` return type and `return this` are tightly coupled to the `this` feature and needed to express method-chaining on instances.

**Independent Test**: Can be tested by compiling a class method declared with return type `Self` that executes `return this`, then verifying the generated PHP uses `: static` return type and `return $this`.

**Acceptance Scenarios**:

1. **Given** a class method with return type `Self`, **When** compiled, **Then** the emitted PHP method signature uses `: static`
2. **Given** a class method that executes `return this`, **When** compiled, **Then** the emitted PHP contains `return $this`
3. **Given** a method typed `Self` that returns `this`, **When** the result is assigned and chained, **Then** the chain compiles without type errors

---

### User Story 3 — `this` inside arrow functions defined within a method (Priority: P2)

A PHireScript developer uses arrow functions inside class methods. Inside those arrow functions `this` should still refer to the enclosing class instance.

**Why this priority**: Arrow functions capture the outer scope; `this` must propagate through them for common patterns like callbacks and transformations.

**Independent Test**: Can be tested by compiling a class method that defines an arrow function referencing `this.property`, and verifying the generated PHP arrow function accesses `$this->property`.

**Acceptance Scenarios**:

1. **Given** an arrow function defined inside a class method, **When** it references `this.property`, **Then** the emitted PHP contains `$this->property` inside the arrow function body
2. **Given** an arrow function inside a method that calls `this.method()`, **When** compiled, **Then** the emitted PHP arrow function contains `$this->method()`

---

### User Story 4 — `this` is forbidden outside class/type/immutable scope (Priority: P1)

A PHireScript developer accidentally uses `this` at the top level or inside a free function. The compiler must reject this with a clear error.

**Why this priority**: Incorrect use of `this` in PHP outside an object context causes fatal runtime errors; the compiler must catch it early.

**Independent Test**: Can be tested by compiling a `.phs` file that uses `this` at the top level and verifying a `CompileException` (or equivalent checker error) is thrown with a meaningful message.

**Acceptance Scenarios**:

1. **Given** a `.phs` file that uses `this` outside any class/type/immutable declaration, **When** compiled, **Then** the compiler throws a `CheckerException` with a message indicating `this` is not valid outside a class context
2. **Given** a free function (not inside a class) that uses `this`, **When** compiled, **Then** the compiler rejects it with an error

---

### Edge Cases

- `this` inside a nested arrow function (arrow inside arrow inside method) is valid at any depth — the scope tracker propagates the class context without a nesting limit, matching PHP's native `$this` capture behavior in arrow functions.
- What happens when `this` appears in a `handle` (catch) block argument position — i.e., as an expression in an error handler?
- When a property is accessed via `this.prop`, the checker validates its existence using two paths: (1) for pure PHireScript classes, it consults the SymbolTable for declared properties and throws a `CheckerException` if not found; (2) for classes that `extends` an `external` PHP class, it uses Reflection on the parent class to verify the property exists and throws a `CheckerException` if not found.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The compiler MUST recognize `this` as a valid keyword inside methods of `class`, `type`, and `immutable` declarations
- **FR-002**: `this.property` MUST compile to `$this->property` in the emitted PHP
- **FR-002a**: When `this.property` is used and the class is pure PHireScript, the checker MUST verify the property is declared in the class and throw a `CheckerException` if it is not
- **FR-002b**: When `this.property` is used and the class extends an `external` PHP class, the checker MUST use Reflection to verify the property exists on the parent class and throw a `CheckerException` if it does not
- **FR-003**: `this.method()` MUST compile to `$this->method()` in the emitted PHP
- **FR-004**: `this` MUST be valid inside `if`, `else if`, and `else` blocks when those blocks appear inside a class method
- **FR-005**: `this` MUST be valid inside `try`, `handle`, and `always` blocks when those blocks appear inside a class method
- **FR-006**: `this` MUST be valid inside arrow functions defined within a class method body, at any level of arrow function nesting
- **FR-007**: `return this` inside a class method MUST compile to `return $this`
- **FR-008**: A method return type of `Self` MUST compile to `: static` in the PHP method signature
- **FR-009**: The compiler MUST throw a `CheckerException` when `this` is used outside a class, type, or immutable scope
- **FR-010**: `this` used in a free function (top-level or standalone function) MUST produce a compiler error with a message pointing to the invalid usage
- **FR-011**: The sandbox MUST contain new `success` cases demonstrating valid `this` usage in each supported context
- **FR-012**: The sandbox MUST contain at least one `error` case demonstrating that `this` outside a class scope produces a compiler error

### Key Entities

- **`this` keyword**: The self-reference token within class methods; maps to `$this` in PHP
- **`Self` return type**: A return-type annotation meaning "this class's own type"; maps to `: static` in PHP
- **Class scope**: Any `class`, `type`, or `immutable` declaration body; the only valid context for `this`

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: All new sandbox `success` cases compile without errors or warnings
- **SC-002**: All new sandbox `error` cases produce the expected `CheckerException` with a descriptive message
- **SC-003**: `this.property` and `this.method()` in every supported block type (if/else/elseif/try/handle/always/arrow function) generates correct PHP in every corresponding sandbox case
- **SC-004**: A class method with return type `Self` returning `this` compiles and the generated PHP passes `php -l` validation
- **SC-005**: Using `this` outside a class context always produces a compilation error — zero false negatives across error sandbox cases

## Assumptions

- `this` is only valid inside instance methods; static methods are out of scope for this feature
- Property existence on `this.prop` is always validated: via SymbolTable for pure PHireScript classes, and via Reflection for classes that extend an `external` PHP parent
- `type` and `immutable` declarations support `this` using the same rules as `class` since they compile to PHP classes
- Arrow functions inside class methods inherit the class scope for `this` resolution, matching PHP's behavior where arrow functions capture `$this` automatically
- `Self` as a return type is distinct from class-name self-reference; it maps specifically to PHP's `static` keyword (late static binding)
- The `this` keyword conflicts with no existing PHireScript reserved word (to be verified during implementation)
