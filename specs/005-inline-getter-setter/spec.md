# Feature Specification: Inline Getter/Setter Declaration on Class Properties

**Feature Branch**: `005-inline-getter-setter`

**Created**: 2026-06-06

**Status**: Draft

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Simple Public Getter (Priority: P1)

A developer wants to expose a class property for reading without writing the full getter method body.

**Why this priority**: The simplest and most common case — read-only access to a property. This alone delivers significant boilerplate reduction and validates the core `<` parsing path.

**Independent Test**: Compile a class with `< Int id` and verify that a `public function getId(): int` method is generated in the PHP output.

**Acceptance Scenarios**:

1. **Given** a class body with `< Int id`, **When** compiled, **Then** the output contains `public function getId(): int { return $this->id; }` and the property itself is declared `public int $id`.
2. **Given** a class with `< Int id` and NO explicit `getId()` method, **When** compiled, **Then** exactly one `getId()` method appears in the output.
3. **Given** a class with `< Int id` AND an explicit `getId(): Int { return this.id * 2 }`, **When** compiled, **Then** the explicit body is used and no duplicate getter is generated.

---

### User Story 2 — Simple Public Setter (Priority: P1)

A developer wants to expose a class property for writing without writing the full setter method body.

**Why this priority**: Symmetric with the getter — equally common, same parsing path but for `>`. Together with P1 covers the `< >` combined case.

**Independent Test**: Compile a class with `* > Email email` and verify a `public function setEmail(string $email): void` method is generated.

**Acceptance Scenarios**:

1. **Given** a class body with `> String username`, **When** compiled, **Then** the output contains `public function setUsername(string $username): void { $this->username = $username; }`.
2. **Given** `* > Email email` (explicit public marker), **When** compiled, **Then** the setter is `public` and functionally identical to `> Email email` without the `*`.
3. **Given** `< > String username`, **When** compiled, **Then** both `getUsername()` and `setUsername()` methods are generated.

---

### User Story 3 — Visibility Control on Getter/Setter (Priority: P2)

A developer needs different visibility levels on getters and setters, or between the generated methods and the property itself.

**Why this priority**: Covers the `#`, `+` modifier tokens before `<`/`>`. Required for encapsulation patterns like "private write, public read".

**Independent Test**: Compile `# < + > Bool isAdmin` and verify the property is `public`, the getter is `private`, and the setter is `protected`.

**Acceptance Scenarios**:

1. **Given** `# < + > Bool isAdmin`, **When** compiled, **Then**: property = `public bool $isAdmin`, getter = `private function getIsAdmin(): bool`, setter = `protected function setIsAdmin(bool $isAdmin): void`.
2. **Given** `+ < # > # Array metadata`, **When** compiled, **Then**: property = `private array $metadata`, getter = `protected function getMetadata(): array`, setter = `private function setMetadata(array $metadata): void`.
3. **Given** `< # > String secret`, **When** compiled, **Then**: getter = `public`, setter = `private`.

---

### User Story 4 — Getter/Setter on Type, Immutable, and Trait Declarations (Priority: P3)

The same inline getter/setter syntax works on `type`, `immutable`, and `trait` constructs, not just `class`.

**Why this priority**: Consistency across all OOP constructs that share `ClassBodyContext`. Validated once the class path works. Traits in particular benefit from this since they are mixed into classes — getters declared in a trait are inherited by the consuming class.

**Independent Test**: Compile a `type Name as scoped { < String value }` and verify a getter is generated.

**Acceptance Scenarios**:

1. **Given** a `type` declaration with `< String value`, **When** compiled, **Then** a `public function getValue(): string` method is generated.
2. **Given** an `immutable` declaration with `< > Int count`, **When** compiled, **Then** both getter and setter are generated.
3. **Given** a `trait` declaration with `< String label`, **When** compiled, **Then** a `public function getLabel(): string` method is generated in the trait body.

---

### Edge Cases

- What happens when both `<` and `>` have no modifier prefix? → Both default to `public`.
- What is the property visibility when no modifier precedes the type token? → Always `public`.
- What happens when `*` appears before `<` or `>`? → Treated as explicit `public`; identical output to having no modifier.
- What if a property has `<` but the class already has an explicit `getPropertyName()` method? → Explicit method wins; generated getter is suppressed for that property only.
- What if a property has only `>` (setter-only) — can the generated setter still be used? → Yes, the property stays writable with no generated getter.
- Does this affect interface method signatures? → No. Interface bodies do not use `ClassBodyContext` property resolution.
- What happens with nullable property types (`String? name`) and `<`/`>`? → Getter return type and setter parameter type both mirror the property type exactly, including `?`.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The compiler MUST recognize `<` before a type token inside a class/type/immutable body as a getter declaration marker.
- **FR-002**: The compiler MUST recognize `>` before a type token inside a class/type/immutable body as a setter declaration marker.
- **FR-003**: A visibility modifier (`*`, `+`, `#`) immediately preceding `<` MUST set the getter method's visibility; absence of a modifier MUST default to `public`.
- **FR-004**: A visibility modifier immediately preceding `>` MUST set the setter method's visibility; absence of a modifier MUST default to `public`.
- **FR-005**: The visibility of the property itself MUST be determined by the modifier immediately preceding the type keyword, defaulting to `public` when absent.
- **FR-006**: The `*` modifier MUST be accepted as an explicit alias for `public` on getter/setter markers (functionally equivalent to absence of modifier).
- **FR-007**: For each `<` on a property, the compiler MUST generate a method named `get{PascalCaseName}()` returning the property type exactly, including nullability (e.g., `String?` → `?string`).
- **FR-008**: For each `>` on a property, the compiler MUST generate a method named `set{PascalCaseName}(Type $value): void` that assigns the value to the property. If the property type is nullable (e.g., `String?`), the setter parameter MUST also be nullable (`?string $value`).
- **FR-009**: If the class body contains an explicit method whose name matches a generated getter or setter, the explicit method MUST take precedence and the generated version MUST be suppressed.
- **FR-010**: The `<` and `>` tokens MUST continue to function as comparison operators in all expression contexts (method bodies, if conditions, assignments) — this feature only activates in class/type/immutable property declaration lines.
- **FR-011**: Generated getter and setter methods MUST appear after all property declarations in the emitted PHP output.
- **FR-012**: The feature MUST apply uniformly to `class`, `type`, `immutable`, and `trait` declarations.

### Key Entities

- **PropertyNode**: Existing AST node extended to carry `getter` (visibility or null) and `setter` (visibility or null) fields populated during parsing.
- **Generated Method**: A synthesized `MethodDeclarationNode`-compatible structure created during emission from `PropertyNode.getter` / `PropertyNode.setter`, not stored in the AST directly.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: All five example properties from the feature reference (`ExampleGetterSetterClass.phs`) compile to exactly the PHP output shown in `ExampleGetterSetterClass.phc` without manual method declarations.
- **SC-002**: A class with `< >` on all properties produces 2× the number of methods as properties, with zero manually written getter/setter bodies.
- **SC-003**: The override rule is verifiable: a class with `< Int id` and an explicit `getId()` method compiles with exactly one `getId()` in the output.
- **SC-004**: All existing sandbox cases (1–54) continue to pass after the feature is implemented — no regressions on comparison operators or existing property declarations.
- **SC-005**: `composer quality` passes inside `phirescript/` with no new violations after implementation.

## Clarifications

### Session 2026-06-06

- Q: Should `trait` declarations support inline getter/setter syntax, like `class`, `type`, and `immutable`? → A: Yes — traits are included; same syntax applies in trait bodies.
- Q: For nullable properties (e.g., `String? name`), should generated getter/setter use the exact nullable type or strip nullability from the setter? → A: Exact type — getter returns `?string`, setter accepts `?string $value`.

## Assumptions

- Property names are always `camelCase` identifiers; generated method names use `PascalCase` of the property name (`id` → `getId`, `isAdmin` → `getIsAdmin`).
- The generated setter always assigns `$this->propertyName = $value` with no additional logic; custom logic requires an explicit method override.
- `immutable` constructs do not enforce setter restrictions at the language level — that is a runtime concern, not a compiler concern for this feature.
- The `*` modifier already passes through `ModifiersTransform` as `public`; no new scanner tokens are needed.
- Interface method signatures remain unaffected — interface bodies parse method signatures, not property declarations.
