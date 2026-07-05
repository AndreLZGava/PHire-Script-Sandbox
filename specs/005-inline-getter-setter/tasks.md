# Tasks: Inline Getter/Setter Declaration

**Input**: Design documents from `specs/005-inline-getter-setter/`

**Organization**: Tasks grouped by user story — each phase is independently testable.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no shared dependencies)
- **[Story]**: Which user story this task belongs to

---

## Phase 1: Setup

**Purpose**: Create sandbox case directories for validation.

- [x] T001 Create sandbox case directories `samples/success/case_55/` through `samples/success/case_59/`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Extend the AST node and token-accumulation pipeline. All user story phases depend on these three tasks.

**⚠️ CRITICAL**: No user story work can begin until T002, T003, T004 are complete.

- [x] T002 Extend `PropertyNode` to add `?string $getter = null` and `?string $setter = null` fields in `phirescript/src/Compiler/Parser/Ast/Nodes/OOP/PropertyNode.php`
- [x] T003 [P] Extend `ModifiersResolver` in `phirescript/src/Compiler/Parser/Ast/Resolver/Root/ModifiersResolver.php`: add `'<'` and `'>'` to the `MODIFIERS` constant; extend `isTheCase()` to also return `true` for `T_ACCESSORS` tokens
- [x] T004 Extend `PropertyResolver.resolve()` in `phirescript/src/Compiler/Parser/Ast/Resolver/Declaration/PropertyResolver.php` to implement the getter/setter parsing algorithm from `data-model.md` — parse accumulated modifiers, extract `getterVis`/`setterVis`/`propertyVis`, and populate `PropertyNode.getter` and `PropertyNode.setter`

**Checkpoint**: `PropertyNode` now carries getter/setter metadata when `<`/`>` tokens are present. `ModifiersResolver` accumulates them correctly. No emitter changes yet.

---

## Phase 3: User Story 1 — Simple Public Getter (Priority: P1) 🎯 MVP

**Goal**: `< Int id` inside a class body generates a `public function getId(): int` method in the PHP output.

**Independent Test**: Compile `samples/success/case_55/` with `< Int id` and verify `public function getId(): int { return $this->id; }` appears in the output via `CaseValidation.php`.

### Implementation for User Story 1

- [x] T005 [US1] Create `GetterSetterEmitter.php` in `phirescript/src/Compiler/Emitter/OOP/GetterSetterEmitter.php` — implement getter emission: iterate `ClassBodyNode.children`, collect explicit method names from `MethodDeclarationNode` instances, for each `PropertyNode` with non-null `getter` field whose `get{PascalName}` is not in explicit names, emit the getter method using `PhpTypeResolver::phpType()` for the return type
- [x] T006 [US1] Register `GetterSetterEmitter` — N/A: called directly from ClassBodyEmitter, not a NodeEmitter
- [x] T007 [US1] Extend `ClassBodyEmitter.emit()` in `phirescript/src/Compiler/Emitter/OOP/ClassBodyEmitter.php` to call `GetterSetterEmitter` after the existing `foreach ($node->children)` loop
- [x] T008 [P] [US1] Create `samples/success/case_55/Getter.ps` — a class with `< Int id`, `< String name`, and `< Bool active` (all public getter, no setter)
- [x] T009 [US1] Create `samples/success/case_55/CaseValidation.php` asserting that `getId()`, `getName()`, and `getActive()` appear in the compiled output and no setter methods appear

**Checkpoint**: US1 fully functional — `< Type name` generates a getter. Run `php bin/stretch --mode=success` to verify case_55 passes.

---

## Phase 4: User Story 2 — Simple Public Setter (Priority: P1)

**Goal**: `> String username` inside a class body generates a `public function setUsername(string $username): void` method.

**Independent Test**: Compile `samples/success/case_56/` with `> Email email` and `< > String username` and verify both setter and combined getter+setter are generated.

### Implementation for User Story 2

- [x] T010 [US2] Extend `GetterSetterEmitter` in `phirescript/src/Compiler/Emitter/OOP/GetterSetterEmitter.php` to add setter emission: for each `PropertyNode` with non-null `setter` whose `set{PascalName}` is not in explicit names, emit the setter method — parameter type from `PhpTypeResolver::phpType()`, body from `PhpTypeResolver::assignment()` to handle supertype/metatype casting correctly
- [x] T011 [P] [US2] Create `samples/success/case_56/Setter.ps` — a class with `> Email email` (setter only) and `< > String username` (both getter and setter)
- [x] T012 [US2] Create `samples/success/case_56/CaseValidation.php` asserting `setEmail()` and `getUsername()` + `setUsername()` appear; `getEmail()` does NOT appear
- [x] T013 [P] [US2] Create `samples/success/case_57/Combined.ps` — a class with `< > String username`, `< > Int count`, and `< > Bool active` (all combined)
- [x] T014 [US2] Create `samples/success/case_57/CaseValidation.php` asserting all three getters and all three setters are generated

**Checkpoint**: US2 fully functional — `>` generates setters, `< >` generates both. Run `php bin/stretch --mode=success` to verify case_56 and case_57 pass.

---

## Phase 5: User Story 3 — Visibility Control (Priority: P2)

**Goal**: `# < + > Bool isAdmin` produces a private getter and protected setter; property is public.

**Independent Test**: Compile `samples/success/case_58/` matching the reference output in `samples/feature/case_2/ExampleGetterSetterClass.psc` exactly (the 5-property class with all visibility variants).

### Implementation for User Story 3

*No new compiler code needed — visibility is already parsed by T004 and emitted by T005/T010. These tasks validate the algorithm is correct end-to-end.*

- [x] T015 [P] [US3] Create `samples/success/case_58/ExampleGetterSetterClass.ps` — copy from `samples/feature/case_2/ExampleGetterSetterClass.ps` with correct package declaration `pkg PHireScript.Samples58`
- [x] T016 [US3] Create `samples/success/case_58/CaseValidation.php` asserting the exact visibility on each generated method: `getId()` public, `setEmail()` public, `getUsername()` + `setUsername()` public, `getIsAdmin()` private + `setIsAdmin()` protected, `getMetadata()` protected + `setMetadata()` private

**Checkpoint**: US3 fully functional — all visibility combinations work. Run `php bin/stretch --mode=success` to verify case_58 passes.

---

## Phase 6: User Story 4 — Type, Immutable, and Trait Declarations (Priority: P3)

**Goal**: The same `< >` syntax works inside `type`, `immutable`, and `trait` body declarations.

**Independent Test**: Compile `samples/success/case_59/` containing a `trait`, a `type`, and an `immutable` each using `< >` markers; verify getters/setters are generated in all three.

### Implementation for User Story 4

- [x] T017 [US4] Extend `TraitEmitter.emit()` in `phirescript/src/Compiler/Emitter/Declarations/TraitEmitter.php` to call `GetterSetterEmitter` after emitting the trait's children (mirror the ClassBodyEmitter change from T007)
- [x] T018 [P] [US4] Create `samples/success/case_59/TypeAndTrait.ps` — split into ValueHolder.ps, Labeled.ps, Counter.ps (one declaration per file)
- [x] T019 [US4] Create `samples/success/case_59/CaseValidation.php` asserting `getValue()` on the type, `getCount()` + `setCount()` on the immutable, and `getLabel()` on the trait are all present in compiled output

**Checkpoint**: US4 fully functional. Run `php bin/stretch --mode=success` to verify case_59 passes.

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Override suppression validation, quality gates, documentation.

- [x] T020 [P] Create `samples/success/case_60/OverrideTest.ps` — class with `< Int id` and explicit `getId()` override; multiplication skipped (pre-existing limitation)
- [x] T021 Create `samples/success/case_60/CaseValidation.php` asserting exactly one `getId()` in output (no duplicate from generated getter)
- [x] T022 Run `composer quality` inside `phirescript/` and fix any PSR-12, PHPStan, or Rector violations introduced by the new code in `GetterSetterEmitter.php`, `PropertyNode.php`, `ModifiersResolver.php`, `PropertyResolver.php`, `ClassBodyEmitter.php`, `TraitEmitter.php`
- [x] T023 Run `php bin/stretch --mode=success` from sandbox root and confirm all cases 1–60 pass with no regressions
- [x] T024 Update `phirescript/CLAUDE.md` Language Feature Status — move "Getter / Setter on properties" from **Partial** to **Functional**; add sandbox cases 55–60 reference

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 (Setup)**: No dependencies — start immediately
- **Phase 2 (Foundational)**: Depends on Phase 1 — blocks all user stories
  - T003 and T004 can run in parallel (different files)
  - T004 depends on T002 (PropertyNode fields must exist first)
- **Phase 3 (US1)**: Depends on Phase 2 completion
  - T005 → T006 → T007 (sequential: emitter then registration then body caller)
  - T008 can run in parallel with T005 (different directory)
  - T009 depends on T007 + T008
- **Phase 4 (US2)**: Depends on Phase 3 completion (extends GetterSetterEmitter)
  - T011 and T013 can run in parallel (different files)
  - T012 depends on T010 + T011; T014 depends on T010 + T013
- **Phase 5 (US3)**: Depends on Phase 4 completion — no new compiler code, only sandbox cases
  - T015 can run in parallel with Phase 4 cases
- **Phase 6 (US4)**: Depends on Phase 3 (ClassBodyEmitter pattern established)
  - T018 can run in parallel with T017
- **Phase 7 (Polish)**: Depends on all user story phases

### Parallel Opportunities

```bash
# Phase 2 — run in parallel:
T003  # ModifiersResolver (independent file)
# then after T002:
T004  # PropertyResolver (needs PropertyNode fields)

# Phase 3 — run in parallel:
T005  # GetterSetterEmitter (new file)
T008  # case_55 .ps source file (new file)

# Phase 4 — run in parallel:
T011  # case_56 .ps file
T013  # case_57 .ps file

# Phase 7 — run in parallel:
T020  # case_60 .ps file
T022  # composer quality (independent)
```

---

## Implementation Strategy

### MVP (User Stories 1 + 2 only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (PropertyNode + ModifiersResolver + PropertyResolver) — **blocks everything**
3. Complete Phase 3: Getter generation + case_55
4. **STOP and VALIDATE**: `php bin/stretch --mode=success` — case_55 must pass
5. Complete Phase 4: Setter generation + case_56 + case_57
6. **STOP and VALIDATE**: cases 56 and 57 must pass
7. Ship as functional feature — visibility and type/trait/immutable support can follow

### Full Delivery

1. MVP above
2. Phase 5 (visibility validation via case_58) — no new code, just sandbox case
3. Phase 6 (trait support via T017 + case_59)
4. Phase 7 (override test + quality + docs)

---

## Notes

- `GetterSetterEmitter` is the only new file in `phirescript/src/`; all other changes are extensions of existing files
- The override detection (FR-009) is implemented entirely inside `GetterSetterEmitter` — no AST changes needed; it reads explicit method names from `ClassBodyNode.children` at emission time
- `PhpTypeResolver::assignment()` handles supertype/metatype setter bodies — reuse it, do not duplicate
- After T022 (`composer quality`), re-run T023 to confirm quality fixes didn't break compilation
- Sandbox case packages: `PHireScript.Samples55` through `PHireScript.Samples60`
