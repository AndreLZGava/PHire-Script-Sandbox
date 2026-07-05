# Research: Inline Getter/Setter Declaration

**Date**: 2026-06-06

## Decision 1: Token Strategy — T_ACCESSORS vs new tokens

**Decision**: Reuse existing `T_ACCESSORS` infrastructure; add `<` and `>` (T_SYMBOL) recognition to `ModifiersResolver`.

**Rationale**: The Scanner already tokenizes `#<`, `+<`, `*<`, `#>`, `+>`, `*>`, `<>`, `><` as `T_ACCESSORS`. The spaced form (`# < + > Bool isAdmin`) produces individual T_SYMBOL tokens. No scanner changes needed. `ModifiersResolver` simply needs to accept `<` and `>` as accumulation tokens.

**Alternatives considered**:
- Dedicated `GetterModifierResolver` and `SetterModifierResolver` — rejected because the accumulation pattern already used by `ModifiersResolver` is exactly what's needed; a dedicated resolver would duplicate the mechanism.
- New scanner tokens (`T_GETTER`, `T_SETTER`) — rejected; `T_ACCESSORS` already captures combined forms and the spaced form via T_SYMBOL is handled by accumulation.

---

## Decision 2: Where to parse getter/setter visibility — PropertyResolver vs dedicated resolver

**Decision**: Extend `PropertyResolver.resolve()` to parse getter/setter info from the accumulated modifiers array (`consumePrevious()`).

**Rationale**: `PropertyResolver` already calls `consumePrevious()` to get the modifier list. Parsing getter/setter from that list is a natural extension of its existing `handleModifiers()` method. No new resolver needed.

**Alternatives considered**:
- `GetterSetterResolver` as a peer of `PropertyResolver` in `ClassBodyContext` — rejected; this would require two separate parse passes for a single property declaration line.

---

## Decision 3: Where to store getter/setter metadata — PropertyNode fields

**Decision**: Add `?string $getter` and `?string $setter` fields to `PropertyNode`. Value is the visibility string (`'public'`, `'protected'`, `'private'`) or `null` when absent.

**Rationale**: `PropertyNode` is the natural owner — it already holds `modifiers`, `types`, `name`. Getter/setter are direct attributes of the property declaration. Storing visibility as a string (not a bool) avoids a second lookup when emitting.

**Alternatives considered**:
- Synthetic `MethodDeclarationNode` children added during parsing — rejected; the spec states generated methods are synthesized at emission, not stored in the AST directly.

---

## Decision 4: Where to emit generated methods — ClassBodyEmitter extension

**Decision**: Extend `ClassBodyEmitter.emit()` to call a new `GetterSetterEmitter` after iterating `children`. The emitter receives the `ClassBodyNode` and the `EmitContext`, collects explicit method names, then generates getter/setter for each `PropertyNode` that declares them.

**Rationale**: `ClassBodyEmitter` is the single orchestration point for all class body output. Emitting getters/setters here keeps ordering correct (properties first, then methods). The same emitter is used for `type` and `immutable` bodies via `ClassBodyNode`.

**Alternatives considered**:
- Per-property emitter generating its own getter/setter alongside the property declaration — rejected; it would interleave property declarations and methods, breaking the PHP convention of listing all properties before methods.

---

## Decision 5: Trait body support

**Decision**: Apply the same change to `TraitBodyContext` (if it exists) or the context that handles trait body parsing.

**Rationale**: The spec clarification confirmed traits are included. Trait bodies follow the same structural pattern as class bodies.

**Finding**: Checking compiler source, trait body uses `ClassBodyContext` (or equivalent). The same `PropertyResolver` and `ClassBodyEmitter` flow applies — minimal additional work.

---

## Decision 6: Supertype/metatype setter body

**Decision**: Generated setters for supertype properties (`Email`, `Uuid`, etc.) use the same cast pattern as `PhpTypeResolver::assignment()` — e.g., `$this->email = Email::cast($email)`. For metatypes, `$this->val = $val instanceof Type ? $val : new Type($val)`.

**Rationale**: This matches the existing setter semantics implied by the `ExampleGetterSetterClass.psc` reference output, which shows `setEmail(string $email): void { $this->email = $email; return; }`. The simplest consistent approach is to delegate to `PhpTypeResolver::assignment()`.

**Alternatives considered**:
- Always use raw assignment `$this->prop = $value` — rejected for supertypes because it skips validation/casting that supertypes require.

---

## No Outstanding Unknowns

All design questions resolved. Ready for data-model and task generation.
