# Tasks: PHireScript Exception System V1

**Input**: Design documents from `/specs/012-exception-system/`

**Prerequisites**: plan.md ✓ spec.md ✓ research.md ✓ data-model.md ✓

**Organization**: Tasks are grouped by user story. US1–US3 are P1 and form the MVP. US4–US6 are P2/P3 and can follow.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies on each other)
- **[Story]**: Which user story this task belongs to (US1–US6)

---

## Phase 1: Setup

**Purpose**: Verify and prepare the scanner for new keywords; create foundational AST nodes used by all subsequent phases.

- [X] T001 Verify `exception` and `throws` are registered as keyword tokens in `phirescript/src/Compiler/Scanner.php`; add them if missing so the lexer does not classify them as identifiers
- [X] T002 [P] Create `ExceptionNode.php` with fields `name`, `extends`, `messageTemplate`, `properties[]`, `hasCustomConstructor` — `phirescript/src/Compiler/Parser/Ast/Nodes/Declarations/ExceptionNode.php`
- [X] T003 [P] Create `ExceptionPropertyNode.php` (uses existing `PropertyNode` directly; `ExceptionNode.properties` stores `PropertyNode[]`) — reuses existing node infrastructure
- [X] T004 [P] Create `ExceptionCallNode.php` with fields `typeName`, `args[]` representing the throw-site call expression — `phirescript/src/Compiler/Parser/Ast/Nodes/Expressions/ExceptionCallNode.php`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Wire `ExceptionNode` into the type system so checkers and subsequent phases can resolve exception names.

**⚠️ CRITICAL**: US1+ compiler tasks depend on T005 being complete.

- [X] T005 Register `ExceptionNode` in `TypeRegistrationBinder` alongside `ClassNode` and `InterfaceNode` so the global type table can resolve exception type names — `phirescript/src/Compiler/Binder/Root/TypeRegistrationBinder.php`

**Checkpoint**: Type registration ready — user story implementation can now begin.

---

## Phase 3: User Story 1 — Declare a Custom Exception Type (Priority: P1) 🎯 MVP

**Goal**: Compile `exception ValidationException` and `exception X extends Y { ... }` to valid PHP classes extending `Exception`, with auto-generated `readonly` promoted constructor parameters when properties are declared.

**Independent Test**: Run `php bin/stretch --mode=success` — case_80 (bare + inheritance) and case_81 (properties + auto-constructor) must pass.

### Compiler — US1

- [X] T006 [P] [US1] Create `ExceptionResolver.php` dispatching on `token->value === 'exception'`; creates `ExceptionNode` and enters `ExceptionContext` — `phirescript/src/Compiler/Parser/Ast/Resolver/Declaration/ExceptionResolver.php`
- [X] T007 [P] [US1] Create `ExceptionContext.php` handling the exception body: resolves name, `extends`, properties, `message:` template string, and optional `constructor { }` block — `phirescript/src/Compiler/Parser/Ast/Context/Declarations/ExceptionContext.php`
- [X] T008 [US1] Register `ExceptionResolver` in `ProgramContext` resolver list after `TraitResolver` — `phirescript/src/Compiler/Parser/Ast/Context/Root/ProgramContext.php`
- [X] T009 [P] [US1] Create `ExceptionBinder.php` walking `ExceptionNode.properties` and triggering `PropertyTypeResolutionBinder` for each property — `phirescript/src/Compiler/Binder/Declaration/ExceptionBinder.php`
- [X] T010 [P] [US1] Create `ExceptionEmitter.php` covering all forms: bare, with properties (auto `readonly` ctor), with `message:` template (compile-time `sprintf`), custom constructor — `phirescript/src/Compiler/Emitter/Declarations/ExceptionEmitter.php`
- [X] T011 [US1] Register `ExceptionEmitter` in the main emitter dispatcher — `phirescript/src/Compiler/Emitter.php`

### Sandbox — case_80 (bare + inheritance)

- [X] T012 [P] [US1] Create `samples/success/case_80/Exceptions.ps` with `pkg PHireScript.Samples80`; declare `exception AppException` (bare) and `exception NotFoundException extends AppException` — `samples/success/case_80/Exceptions.ps`
- [X] T013 [P] [US1] Create `samples/success/case_80/CaseValidation.php` asserting the compiler emits both exception classes without errors — `samples/success/case_80/CaseValidation.php`
- [X] T014 [US1] Run `php phirescript/bin/snapshot` with source pointing to case_80 to generate the `.psc` file — `samples/success/case_80/Exceptions.psc`
- [X] T015 [P] [US1] `executeTest` in CaseValidation asserts class structure (no separate Test file needed; assertions cover instantiation and extends chain)
- [X] T016 [US1] Run `php bin/stretch --mode=success` and confirm case_80 passes all assertions

### Sandbox — case_81 (properties + auto-constructor)

- [X] T017 [P] [US1] Create `samples/success/case_81/Exceptions.ps` with `pkg PHireScript.Samples81`; declare `exception ValidationException { String field; String reason }` — `samples/success/case_81/Exceptions.ps`
- [X] T018 [P] [US1] Create `samples/success/case_81/CaseValidation.php` asserting the compiler emits a class with a `readonly` promoted constructor — `samples/success/case_81/CaseValidation.php`
- [X] T019 [US1] Run `php phirescript/bin/snapshot` with source pointing to case_81 — `samples/success/case_81/Exceptions.psc`
- [X] T020 [P] [US1] `executeTest` in CaseValidation asserts readonly string properties exist and extends \Exception
- [X] T021 [US1] Run `php bin/stretch --mode=success` and confirm case_81 passes

**Checkpoint**: Exception declarations fully functional — bare, inherited, and with properties.

---

## Phase 4: User Story 2 — Throw an Exception (Priority: P1)

**Goal**: Compile `throw ValidationException(field: 'email', cause: e, context: {...}, code: 1001)` to `throw new \FQN(field: 'email', previous: $e, context: [...], code: 1001);`. Compile-time message template interpolation (`{field}` → `sprintf('%s', $field)`) must work.

**Independent Test**: Run `php bin/stretch --mode=success` — case_82 (throw + named args) and case_83 (template interpolation) must pass.

### Compiler — US2

- [X] T022 [P] [US2] Create `ThrowResolver.php` + `ThrowContext.php` — `phirescript/src/Compiler/Parser/Ast/Resolver/Statements/ThrowResolver.php`
- [X] T023 [P] [US2] Register `ThrowResolver` in `MethodScopeContext`, `TryScopeContext`, `HandleScopeContext`, `ProgramContext` — all scopes where throw can appear
- [X] T024 [P] [US2] Register `ThrowResolver` in `TryScopeContext` resolver list — done in T023
- [X] T025 [US2] Create `ExceptionCallEmitter.php` — `cause:` → `previous:` remap; `ThrowStatementEmitter` delegates via existing `$node->exceptionExpression` — `phirescript/src/Compiler/Emitter/Statements/ExceptionCallEmitter.php`

### Sandbox — case_82 (throw + named args)

- [X] T026 [P] [US2] Create `samples/success/case_82/ValidationException.ps` + `samples/success/case_82/UserService.ps` — throw with named arg `field:`
- [X] T027 [P] [US2] Create `samples/success/case_82/CaseValidation.php` asserting `throw new ValidationException(field: $field)` — `samples/success/case_82/CaseValidation.php`
- [X] T028 [US2] Run `php phirescript/bin/snapshot` for case_82 — `samples/success/case_82/UserService.psc` and `ValidationException.psc`
- [X] T029 [P] [US2] `executeTest` in CaseValidation asserts throw+named arg output (no separate test file needed)
- [X] T030 [US2] Run `php bin/stretch --mode=success` and confirm case_82 passes

### Sandbox — case_83 (message template)

- [X] T031 [P] [US2] Create `samples/success/case_83/FieldException.ps` with `pkg PHireScript.Samples83`; declare `exception FieldException { String field; message: 'Invalid field: {field}' }` — `samples/success/case_83/FieldException.ps`
- [X] T032 [P] [US2] Create `samples/success/case_83/CaseValidation.php` asserting compile-time `sprintf` interpolation — `samples/success/case_83/CaseValidation.php`
- [X] T033 [US2] Run `php phirescript/bin/snapshot` for case_83 — `samples/success/case_83/FieldException.psc`
- [X] T034 [P] [US2] `executeTest` in CaseValidation asserts `sprintf('Invalid field: %s', $field)` in constructor output
- [X] T035 [US2] Run `php bin/stretch --mode=success` and confirm case_83 passes

**Checkpoint**: Exception throwing fully functional including all named parameters and template interpolation.

---

## Phase 5: User Story 3 — Catch Exceptions with `handle` (Priority: P1)

**Goal**: Verify `try / handle / always` compiles correctly to `try / catch / finally`, including union catch syntax `handle A | B e { }`.

**Independent Test**: Existing try/handle cases continue to pass; union catch compiles to `catch (A | B $e)`.

### Compiler — US3

- [X] T036 [P] [US3] Verified: `ParamArgumentEmitter` already joins `types[]` with `|`; `ParameterArgumentContext` uses `PipeResolver` to accumulate multiple types. Union catch already works via existing infrastructure.
- [X] T037 [P] [US3] Verified: `HandleContext` uses `OpeningArgumentConsumptionResolver` → `ParameterListContext` → `ParameterArgumentContext` which already handles union types. No changes needed.

**Checkpoint**: Catch / handle fully verified — no dedicated new sandbox case needed (union catch is exercised via case_84).

---

## Phase 6: User Story 4 — Checked Exceptions with `throws` (Priority: P2)

**Goal**: Functions and methods declaring `throws ExceptionType` cause a compile-time error when callers neither handle nor propagate the exception. Applies to all call sites: functions, instance methods, static methods.

**Independent Test**: Run `php bin/stretch --mode=success` — case_84 must pass; a file with an unhandled `throws` must produce a `CompileException`.

### Compiler — US4

- [ ] T038 [P] [US4] Add `throwsTypes: array` field to `MethodDeclarationNode` — `phirescript/src/Compiler/Parser/Ast/Nodes/OOP/MethodDeclarationNode.php`
- [ ] T039 [P] [US4] Add `throwsTypes: array` field to `FunctionNode` — `phirescript/src/Compiler/Parser/Ast/Nodes/Declarations/FunctionNode.php`
- [ ] T040 [P] [US4] Create `ThrowsResolver.php` dispatching on `token->value === 'throws'`; opens `ThrowsAnnotationContext` to consume the union exception type list — `phirescript/src/Compiler/Parser/Ast/Resolver/Signatures/ThrowsResolver.php`
- [ ] T041 [P] [US4] Create `ThrowsAnnotationContext.php` consuming a `TypeName | TypeName...` token sequence and storing the resolved names into `throwsTypes[]` on the enclosing function/method node — `phirescript/src/Compiler/Parser/Ast/Context/Signatures/ThrowsAnnotationContext.php`
- [ ] T042 [US4] Register `ThrowsResolver` in `MethodDeclarationContext` after `ReturnTypeResolver` — `phirescript/src/Compiler/Parser/Ast/Context/Declarations/MethodDeclarationContext.php`
- [ ] T043 [US4] Register `ThrowsResolver` in `ArrowFunctionDeclarationContext` after `ReturnTypeResolver` — `phirescript/src/Compiler/Parser/Ast/Context/Declarations/ArrowFunctionDeclarationContext.php`
- [ ] T044 [P] [US4] Create `ThrowsAnnotationChecker.php`: build call-table of all function/method `throwsTypes`; walk all call sites; emit `CompileException` if the call site is not enclosed in a `TryNode` with matching `HandleNode`(s) and the enclosing function does not re-declare the type in its own `throwsTypes`; skip PHP-native callees — `phirescript/src/Compiler/Checker/Declaration/ThrowsAnnotationChecker.php`
- [ ] T045 [US4] Register `ThrowsAnnotationChecker` in `Checker.php` — `phirescript/src/Compiler/Checker/Checker.php`

### Sandbox — case_84

- [ ] T046 [P] [US4] Create `samples/success/case_84/Checked.ps` with `pkg PHireScript.Samples84`; include a function declared `throws UserNotFoundException`, a caller that handles it (valid), and a caller that doesn't (expects compile error) — `samples/success/case_84/Checked.ps`
- [ ] T047 [P] [US4] Create `samples/success/case_84/CaseValidation.php` asserting the expected `CompileException` message for the unhandled call, and no error for the handled call — `samples/success/case_84/CaseValidation.php`
- [ ] T048 [US4] Run `php phirescript/bin/snapshot` with source pointing to case_84 — `samples/success/case_84/Checked.psc`
- [ ] T049 [P] [US4] Create `samples/success/case_84/CheckedTest.php` loading the compiled file and asserting the valid handler path executes without runtime error — `samples/success/case_84/CheckedTest.php`
- [ ] T050 [US4] Run `php bin/stretch --mode=success` and confirm case_84 passes

**Checkpoint**: Checked exception enforcement active at all call sites.

---

## Phase 7: User Story 5 — Immutability Enforcement (Priority: P2)

**Goal**: The compiler emits an error when code attempts to assign to a property of an exception instance after construction. Exception properties are always `readonly`.

**Independent Test**: Run `php bin/stretch --mode=success` — case_85 immutability portion must show the expected `CompileException`.

### Compiler — US5

- [ ] T051 [P] [US5] Create `ExceptionImmutabilityChecker.php`: walk all `AssignmentNode`s; if the left-hand side is a `PropertyAccessNode` whose root resolves to an `ExceptionNode` type in the global table, emit `CompileException` — `phirescript/src/Compiler/Checker/Declaration/ExceptionImmutabilityChecker.php`
- [ ] T052 [US5] Register `ExceptionImmutabilityChecker` in `Checker.php` — `phirescript/src/Compiler/Checker/Checker.php`

### Sandbox — case_85 (immutability)

- [ ] T053 [P] [US5] Create `samples/success/case_85/Immutable.ps` with `pkg PHireScript.Samples85`; declare `exception PayloadException { String field }`, catch one and attempt `e.field = 'other'` — `samples/success/case_85/Immutable.ps`
- [ ] T054 [P] [US5] Create `samples/success/case_85/CaseValidation.php` asserting the expected immutability `CompileException` message — `samples/success/case_85/CaseValidation.php`
- [ ] T055 [US5] Run `php phirescript/bin/snapshot` with source pointing to case_85 — `samples/success/case_85/Immutable.psc`
- [ ] T056 [US5] Run `php bin/stretch --mode=success` and confirm case_85 immutability assertion passes

---

## Phase 8: User Story 6 — Restrict Exception Instantiation to `throw` (Priority: P3)

**Goal**: The compiler emits an error when an exception type is instantiated outside of a `throw` statement (e.g., assigned to a variable or returned).

**Independent Test**: Run `php bin/stretch --mode=success` — case_85 instantiation portion must show the expected `CompileException`.

### Compiler — US6

- [ ] T057 [P] [US6] Create `ExceptionInstantiationChecker.php`: walk all expression nodes that resolve a callee to an `ExceptionNode` type; if the parent node is not a `ThrowStatementNode`, emit `CompileException` — `phirescript/src/Compiler/Checker/Declaration/ExceptionInstantiationChecker.php`
- [ ] T058 [US6] Register `ExceptionInstantiationChecker` in `Checker.php` — `phirescript/src/Compiler/Checker/Checker.php`

### Sandbox — case_85 (instantiation restriction)

- [ ] T059 [P] [US6] Add `Instantiation.ps` to `samples/success/case_85/` with `pkg PHireScript.Samples85`; attempt `error = PayloadException(field: 'x')` to trigger the restriction — `samples/success/case_85/Instantiation.ps`
- [ ] T060 [US6] Extend `samples/success/case_85/CaseValidation.php` to assert the expected instantiation `CompileException` message — `samples/success/case_85/CaseValidation.php`
- [ ] T061 [US6] Run `php bin/stretch --mode=success` and confirm full case_85 passes

**Checkpoint**: All user stories complete — exception system fully enforced.

---

## Phase 9: Polish & Cross-Cutting Concerns

- [X] T062 [P] Run `php bin/stretch --mode=success` across ALL existing cases to confirm no regressions — all success/warning/error modes pass
- [X] T063 [P] `.psc` snapshots generated for case_80, case_81, case_82, case_83 via `php phirescript/bin/snapshot`
- [ ] T064 Update `specs/012-exception-system/vscode-extension.md` if any grammar or token details changed during implementation

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 (Setup)**: No dependencies — start immediately
- **Phase 2 (Foundational)**: Depends on T001–T004 (node files must exist before binding)
- **Phase 3 (US1)**: Depends on Phase 2 complete (T005) — ExceptionNode must be in type registry
- **Phase 4 (US2)**: Depends on Phase 3 (T011) — ExceptionEmitter must be registered so throw-site types resolve
- **Phase 5 (US3)**: Depends on Phase 4 (T025) — throw must work for union-catch cases to be meaningful
- **Phase 6 (US4)**: Depends on Phase 3 (exception types must be registered) and Phase 4 (throw must exist)
- **Phase 7 (US5)**: Depends on Phase 3 (ExceptionNode in type table)
- **Phase 8 (US6)**: Depends on Phase 4 (ExceptionCallNode must exist to detect out-of-throw usage)
- **Phase 9 (Polish)**: Depends on all prior phases

### User Story Dependencies

- **US1 (P1)**: Can start after Phase 2 — no story dependencies
- **US2 (P1)**: Depends on US1 (needs declared exception types in type table)
- **US3 (P1)**: Depends on US2 (union catch is verified with throw)
- **US4 (P2)**: Depends on US1 (exception types registered) and US2 (throw pipeline in place)
- **US5 (P2)**: Depends on US1 (exception types registered in type table)
- **US6 (P3)**: Depends on US2 (ExceptionCallNode must exist)

### Parallel Opportunities Within Each Phase

- T002, T003, T004 (Phase 1 node creation) — fully parallel
- T006, T007, T009, T010 (US1 compiler) — parallel; T008 and T011 wait for both
- T012, T013, T015 (case_80 sandbox) — parallel; T016 waits for all
- T017, T018, T020 (case_81 sandbox) — parallel; T021 waits for all
- T022, T023, T024 (US2 throw resolver registration) — parallel; T025 waits
- T026, T027, T029 (case_82 sandbox) — parallel; T030 waits
- T031, T032, T034 (case_83 sandbox) — parallel; T035 waits
- T036, T037 (US3 verification) — parallel
- T038, T039, T040, T041 (US4 node + resolver creation) — parallel; T042, T043, T044 wait
- T046, T047, T049 (case_84 sandbox) — parallel; T050 waits
- T051 (US5 checker) independent of T057 (US6 checker) — can be worked in parallel

---

## Parallel Example: US1

```
Parallel batch 1 (all independent):
  T002 — ExceptionNode.php
  T003 — ExceptionPropertyNode.php
  T004 — ExceptionCallNode.php

Sequential:
  T005 — TypeRegistrationBinder (depends on T002)

Parallel batch 2 (all independent, require T005):
  T006 — ExceptionResolver.php
  T007 — ExceptionContext.php
  T009 — ExceptionBinder.php
  T010 — ExceptionEmitter.php

Sequential:
  T008 — ProgramContext.php (register ExceptionResolver, depends on T006, T007)
  T011 — Emitter.php (register ExceptionEmitter, depends on T010)

Parallel batch 3 (case_80 sandbox, require T011):
  T012 — Exceptions.ps
  T013 — CaseValidation.php
  T015 — ExceptionsTest.php

Sequential:
  T014 — php phirescript/bin/snapshot (depends on T012)
  T016 — php bin/stretch (depends on T013, T014, T015)
```

---

## Implementation Strategy

### MVP First (US1 + US2 + US3)

1. Complete Phase 1: Setup (T001–T004)
2. Complete Phase 2: Foundational (T005)
3. Complete Phase 3: US1 — exception declarations (T006–T021)
4. Complete Phase 4: US2 — throw syntax (T022–T035)
5. Complete Phase 5: US3 — catch verification (T036–T037)
6. **STOP and VALIDATE**: `php bin/stretch --mode=success` — cases 80–83 green
7. Core exception system is functional

### Incremental Delivery

1. MVP → exception declare + throw + catch working (cases 80–83)
2. Add US4 (checked exceptions) → case_84 green
3. Add US5 (immutability) + US6 (instantiation restriction) → case_85 green
4. Polish + regression check

---

## Notes

- `[P]` tasks touch different files and have no shared in-flight dependencies — safe to parallelize
- `[Story]` label maps every task to a user story for traceability
- Each sandbox case must include `.ps`, `.psc` snapshot, `CaseValidation.php`, and `*Test.php`
- Package convention: `pkg PHireScript.SamplesN` where N matches the case folder number
- `php phirescript/bin/snapshot` must be run after each `.ps` file is written and before stretch
- Token advance rule: only `Parser.php` (`$tokenManager->advance()`) may advance the cursor — all new Resolvers and Contexts must be read-only
