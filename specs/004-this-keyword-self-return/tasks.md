# Tasks: this Keyword and Self Return Type

**Input**: Design documents from `specs/004-this-keyword-self-return/`

**Branch**: `004-this-keyword-self-return`

**Organization**: Tasks are grouped by user story to enable independent implementation and testing.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no shared dependencies)
- **[Story]**: Which user story this task belongs to (US1–US4)
- Compiler files: `phirescript/src/Compiler/`
- Sandbox files: `samples/`

---

## Phase 1: Foundational (Blocking Prerequisites)

**Purpose**: Create the `ThisResolver` and wire it into the base method scope. All user stories depend on this. `class`, `type`, and `immutable` all use `ClassContext`, so a single `isIn(ClassContext::class)` covers the scope check.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete.

- [ ] T001 Create `ThisResolver` in `phirescript/src/Compiler/Parser/Ast/Resolver/Expressions/ThisResolver.php` — matches `token->value === 'this'` (T_KEYWORD), creates `ThisExpressionNode($token)`, calls `$parseContext->variables->setVirtualVariable($node)`, adds to context children
- [ ] T002 Create `ThisPropertyAccessResolver` in `phirescript/src/Compiler/Parser/Ast/Resolver/Expressions/ThisPropertyAccessResolver.php` — matches: focus is `ThisExpressionNode` + token is identifier + next token is NOT `(` — creates `PropertyAccessNode($token, $focus, $token->value)`, sets as virtual variable, adds to context children (model: `ExternalPropertyAccessResolver`)
- [ ] T003 Create `ThisScopeChecker` in `phirescript/src/Compiler/Checker/Expression/ThisScopeChecker.php` — `#[CompilerPass(order: 3)]`, `mustCheck`: `$node instanceof ThisExpressionNode`, `check`: calls `$checker->parseContext->contextManager->isIn(ClassContext::class)`, throws `CheckerException` with message `"'this' is not valid outside a class, type, or immutable context"` if not in class scope
- [ ] T004 Add `ThisResolver` and `ThisPropertyAccessResolver` to `MethodScopeContext` in `phirescript/src/Compiler/Parser/Ast/Context/Scopes/MethodScopeContext.php` — insert before `FunctionCallResolver` in the resolvers array; `ThisPropertyAccessResolver` after `DotResolver`

**Checkpoint**: `this` inside a basic class method compiles to `$this->property` / `$this->method()`. Ready for US1 sandbox case.

---

## Phase 2: User Story 1 — `this` inside class methods (Priority: P1) 🎯 MVP

**Goal**: `this.property` and `this.method()` work inside plain class methods. Compile to `$this->property` and `$this->method()`.

**Independent Test**: `php bin/stretch --mode=success` passes case_50 with no errors or warnings.

### Implementation for User Story 1

- [ ] T005 [US1] Add `ThisResolver` and `ThisPropertyAccessResolver` to `IfScopeContext` in `phirescript/src/Compiler/Parser/Ast/Context/Scopes/IfScopeContext.php`
- [ ] T006 [P] [US1] Add `ThisResolver` and `ThisPropertyAccessResolver` to `ElseScopeContext` in `phirescript/src/Compiler/Parser/Ast/Context/Scopes/ElseScopeContext.php`
- [ ] T007 [P] [US1] Add `ThisResolver` and `ThisPropertyAccessResolver` to `ElseIfScopeContext` in `phirescript/src/Compiler/Parser/Ast/Context/Scopes/ElseIfScopeContext.php`
- [ ] T008 [P] [US1] Add `ThisResolver` and `ThisPropertyAccessResolver` to `TryScopeContext` in `phirescript/src/Compiler/Parser/Ast/Context/Scopes/TryScopeContext.php`
- [ ] T009 [P] [US1] Add `ThisResolver` and `ThisPropertyAccessResolver` to `HandleScopeContext` in `phirescript/src/Compiler/Parser/Ast/Context/Scopes/HandleScopeContext.php`
- [ ] T010 [P] [US1] Add `ThisResolver` and `ThisPropertyAccessResolver` to `AlwaysScopeContext` in `phirescript/src/Compiler/Parser/Ast/Context/Scopes/AlwaysScopeContext.php`
- [ ] T011 [US1] Create sandbox case `samples/success/case_50/` — a class with a String property `name`, a method `getName()` that returns `this.name`, and a method `reset()` that calls `this.setName('default')` inside an `if` block. `CaseValidation.php` asserts compilation success and generated PHP contains `$this->name` and `$this->setName`
- [ ] T012 [US1] Generate snapshot for case_50: `php phirescript/bin/snapshot samples/success/case_50/<MainFile>.ps`
- [ ] T013 [US1] Run `php bin/stretch --mode=success` and confirm case_50 passes with zero PHP warnings

**Checkpoint**: case_50 passes. `this.property` and `this.method()` verified in plain method body, if/else blocks, try/handle/always blocks.

---

## Phase 3: User Story 2 — `return this` and `Self` return type (Priority: P1)

**Goal**: Method return type `Self` compiles to `: static`; `return this` inside a method compiles to `return $this`.

**Independent Test**: `php bin/stretch --mode=success` passes case_54 and generated PHP passes `php -l`.

### Implementation for User Story 2

- [ ] T014 [US2] Add `ThisResolver` to `ReturnContext` resolvers list in `phirescript/src/Compiler/Parser/Ast/Context/Statements/ReturnContext.php` — enables `return this` to parse as `ReturnNode { expression: ThisExpressionNode }`
- [ ] T015 [US2] Extend `ReturnTypeContext` to accept `Self` keyword in `phirescript/src/Compiler/Parser/Ast/Context/Signatures/ReturnTypeContext.php` — add a resolver (or extend the existing handle) that matches `$token->isKeyword() && $token->value === 'Self'` and adds `'Self'` to context children (same as `TypeResolver` does for primitives)
- [ ] T016 [US2] Update `ReturnTypeEmitter` in `phirescript/src/Compiler/Emitter/OOP/ReturnTypeEmitter.php` — before the `mb_strtolower` loop, check: `if ($type === 'Self') { $types[] = 'static'; continue; }` to avoid lowercasing to `self` instead of `static`
- [ ] T017 [US2] Create sandbox case `samples/success/case_54/` — a class `Builder` with a String property `name`, a method `setName(name: String): Self` that sets `this.name` and returns `this`, and a method `build(): String` that returns `this.name`. `CaseValidation.php` asserts compilation success and generated PHP contains `: static` and `return $this`
- [ ] T018 [US2] Generate snapshot for case_54: `php phirescript/bin/snapshot samples/success/case_54/<MainFile>.ps`
- [ ] T019 [US2] Run `php bin/stretch --mode=success` and confirm case_54 passes; verify generated PHP passes `php -l`

**Checkpoint**: case_54 passes. Fluent builder pattern with `Self` and `return this` is functional.

---

## Phase 4: User Story 3 — `this` inside arrow functions (Priority: P2)

**Goal**: `this.property` and `this.method()` are valid inside arrow functions defined within class methods, at any nesting depth.

**Independent Test**: `php bin/stretch --mode=success` passes case_53 with no errors or warnings.

### Implementation for User Story 3

- [ ] T020 [US3] Add `ThisResolver` and `ThisPropertyAccessResolver` to `ArrowFunctionDeclarationContext` in `phirescript/src/Compiler/Parser/Ast/Context/Declarations/ArrowFunctionDeclarationContext.php`
- [ ] T021 [US3] Create sandbox case `samples/success/case_53/` — a class with a `List<String>` property, a method `getFiltered(prefix: String)` that uses an arrow function `(item: String): Bool => item.startsWith(prefix)` AND references `this.items` inside the arrow body; also a doubly-nested arrow (arrow inside arrow) that references `this.name`. `CaseValidation.php` asserts compilation success and generated PHP contains `$this->` inside the arrow function body
- [ ] T022 [US3] Generate snapshot for case_53: `php phirescript/bin/snapshot samples/success/case_53/<MainFile>.ps`
- [ ] T023 [US3] Run `php bin/stretch --mode=success` and confirm case_53 passes with zero PHP warnings

**Checkpoint**: case_53 passes. `this` inside arrow functions (including nested arrows) works correctly.

---

## Phase 5: User Story 4 — `this` forbidden outside class scope (Priority: P1)

**Goal**: Using `this` at the top level or inside a free function produces a `CheckerException` with a clear message.

**Independent Test**: `php bin/stretch --mode=error` passes the new error case.

### Implementation for User Story 4

- [ ] T024 [US4] Create sandbox error case `samples/error/case_X/` (use next available number) — a `.ps` file with a top-level `this.name` statement and no class declaration. `CaseValidation.php` asserts the compiler throws with a message matching `"'this' is not valid outside"` (or equivalent)
- [ ] T025 [US4] Run `php bin/stretch --mode=error` and confirm the error case is caught with the expected message; verify no false positives in success cases

**Checkpoint**: Error case passes. `this` outside class scope is reliably rejected.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Additional sandbox coverage, snapshot generation for all new cases, PHPStan validation.

- [ ] T026 [P] Create sandbox case `samples/success/case_51/` — focused on `this` inside `if/elseif/else` blocks: a class `StatusChecker` with a Bool property `active`, a method `toggle()` that uses `if this.active { ... } else { ... }` and assigns `this.active` in each branch. `CaseValidation.php` asserts compilation success
- [ ] T027 [P] Create sandbox case `samples/success/case_52/` — focused on `this` inside `try/handle/always` blocks: a class `SafeLogger` with a String property `log`, a method `record(msg: String)` that has a `try { this.log = msg } handle (e: Exception) { this.log = 'error' } always { this.flush() }`. `CaseValidation.php` asserts compilation success
- [ ] T028 Generate snapshots for case_51 and case_52: `php phirescript/bin/snapshot samples/success/case_51/<File>.ps` and `case_52/<File>.ps`
- [ ] T029 Run `composer quality` inside `phirescript/` and fix any PHPStan level 9 violations introduced by new files (`ThisResolver`, `ThisPropertyAccessResolver`, `ThisScopeChecker`)
- [ ] T030 Run full `php bin/stretch --mode=success` and confirm all existing cases still pass (regression check)
- [ ] T031 Run full `php bin/stretch --mode=error` and confirm all error cases pass

---

## Dependencies & Execution Order

### Phase Dependencies

- **Foundational (Phase 1)**: No dependencies — start here. T001, T002, T003 can run in parallel; T004 depends on T001+T002
- **US1 (Phase 2)**: Depends on Phase 1 complete. T005–T010 can run in parallel; T011 depends on T005–T010
- **US2 (Phase 3)**: Depends on Phase 1 (T014 needs `ReturnContext` + `ThisResolver`). Independent from US1 in terms of code; T015+T016 can run in parallel
- **US3 (Phase 4)**: Depends on Phase 1. T020 can start as soon as Phase 1 done
- **US4 (Phase 5)**: Depends on T003 (ThisScopeChecker). Can start right after Phase 1
- **Polish (Phase 6)**: Depends on all US phases complete

### User Story Dependencies

- **US1 (P1)**: Depends on Foundational only
- **US2 (P1)**: Depends on Foundational + T014 (ReturnContext). Independent from US1
- **US3 (P2)**: Depends on Foundational only. Independent from US1/US2
- **US4 (P1)**: Depends on T003 (ThisScopeChecker) only

### Parallel Opportunities

| Group | Tasks | Can run together |
|-------|-------|-----------------|
| Foundational new files | T001, T002, T003 | Yes — different files |
| Scope context wiring (US1) | T005, T006, T007, T008, T009, T010 | Yes — each is a different context file |
| ReturnType work (US2) | T015, T016 | Yes — different files |
| Additional cases (Phase 6) | T026, T027 | Yes — different directories |

---

## Implementation Strategy

### MVP First (US1 + US4 — both P1)

1. Complete Phase 1: Foundational (T001–T004)
2. Complete Phase 2: US1 (T005–T013)
3. Complete Phase 5: US4 (T024–T025)
4. **STOP and VALIDATE**: `php bin/stretch --mode=success` and `--mode=error` both pass
5. `this` in method bodies is functional and safe

### Incremental Delivery

1. Foundational (Phase 1) → `ThisResolver` wired into method scope
2. US1 (Phase 2) → `this` in all block types within methods ✓
3. US2 (Phase 3) → `return this` + `Self` return type ✓
4. US3 (Phase 4) → `this` in arrow functions ✓
5. US4 (Phase 5) → error case validated ✓
6. Polish (Phase 6) → PHPStan clean, full regression green ✓

---

## Notes

- `class`, `type`, and `immutable` ALL use `ClassContext` in the parser — one `isIn(ClassContext::class)` check covers all three
- `PropertyAccessNode` + `PropertyAccessEmitter` already exist (used by `external`) — `ThisPropertyAccessResolver` reuses them directly
- `ThisExpressionEmitter` is already registered in `Emitter.php:125` — no changes needed there
- The `[P]` scope context tasks (T005–T010) each touch a different file — safe to run in parallel
- Run `php bin/stretch` after each phase checkpoint to catch regressions early
- PHPStan check is in Phase 6 but can be run incrementally after each new PHP file is created
