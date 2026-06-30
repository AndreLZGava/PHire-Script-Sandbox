---

description: "Task list for 007-fix-dot-resolver implementation"
---

# Tasks: DotResolver Fix — Chain Emit in Assignment/Return Contexts

**Input**: Design documents from `specs/007-fix-dot-resolver/`

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2, US3)

---

## Phase 1: Setup

**Purpose**: Understand the current codebase state before making changes.

- [X] T001 Read `phirescript/src/Compiler/Emitter/Declarations/FunctionEmitter.php` — locate `overrideSelf()` and `wrapAsIIFE()` methods and understand their full signatures
- [X] T002 [P] Identify all call sites of `wrapAsIIFE` inside `FunctionEmitter.php` to confirm which paths must remain untouched
- [X] T003 [P] Read existing method-chain sandbox cases (case_42 through case_49) to confirm baseline behaviour and understand what already compiles correctly

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Confirm the exact bug location before writing any fix.

**⚠️ CRITICAL**: No implementation can begin until the emit path is confirmed.

- [X] T004 Trace the emit path for `result = this.label.toUpperCase().removeSpaces()` — read `DotResolver` (Statements), `FunctionCallResolver`, and confirm `$node->method->phpCodeForConversion` is an array for `removeSpaces`
- [X] T005 [P] Identify the exact line in `wrapAsIIFE` where `$variable` is placed into `use (...)` — confirm this is what produces invalid PHP when `$variable` is a compound expression
- [X] T006 [P] Run `php bin/stretch --mode=success` and record the current pass/fail count as a regression baseline

**Checkpoint**: Root cause confirmed at the exact line in `wrapAsIIFE`, baseline recorded — implementation can begin.

---

## Phase 3: User Story 1 — Chain emit in assignment context (Priority: P1) 🎯 MVP

**Goal**: `result = this.label.toUpperCase().removeSpaces()` compiles to `$result = \trim(\mb_strtoupper($this->label, 'UTF-8'));` with no closure.

**Independent Test**: Create `samples/success/case_67/` with a method that assigns a two-call chain. `php bin/stretch --mode=success` must pass case_67.

### Implementation for User Story 1

- [X] T007 [P] [US1] Create `samples/success/case_67/ChainAssignment.ps` — declare `pkg PHireScript.Samples67`, class `ChainAssignment as scoped` with `String label` property and method `processAssignment(): String` that assigns `this.label.toUpperCase().removeSpaces()` to `result` and returns it
- [X] T008 [P] [US1] Add `private function emitChainedExpression(array $lines, string $self, $node, $ctx): string` to `phirescript/src/Compiler/Emitter/Declarations/FunctionEmitter.php` — for single-element arrays starting with `return `, extract and inline the expression replacing `@self` with `$self`; for multi-statement arrays, materialise `$self` into a `$__chain_N` temp var
- [X] T009 [US1] Update `overrideSelf()` in `phirescript/src/Compiler/Emitter/Declarations/FunctionEmitter.php` — when `$method` is array, call `$this->emitChainedExpression($method, $variable, $node, $ctx)` instead of passing to `wrapAsIIFE()`
- [X] T010 [US1] Create `samples/success/case_67/CaseValidation.php` extending `AbstractCaseValidation` — assert compilation success message and add a PHPUnit test that instantiates `ChainAssignment`, sets `label` to `'  hello  '`, calls `processAssignment()`, and asserts the result equals `'HELLO'`
- [X] T011 [US1] Run `php bin/stretch --mode=success` and confirm case_67 passes with the generated PHP containing `\trim(\mb_strtoupper(...))`

**Checkpoint**: case_67 passes — assignment chain emits clean PHP without closures.

---

## Phase 4: User Story 2 — Chain emit in return context (Priority: P2)

**Goal**: `return this.label.toUpperCase().removeSpaces()` compiles to `return \trim(\mb_strtoupper($this->label, 'UTF-8'));` with no intermediate variable.

**Independent Test**: Add `processReturn()` to case_67's class. Verify the PHP output contains `return \trim(...)` with no extra assignment.

### Implementation for User Story 2

- [X] T012 [US2] Add `processReturn(): String { return this.label.toUpperCase().removeSpaces() }` to `samples/success/case_67/ChainAssignment.ps`
- [X] T013 [US2] Update `samples/success/case_67/CaseValidation.php` — extend the PHPUnit test to also call `processReturn()` and assert it returns the same trimmed uppercase value
- [X] T014 [US2] Run `php bin/stretch --mode=success` and confirm case_67 still passes with the return scenario covered, and that the emitted PHP is `return \trim(\mb_strtoupper(...))` (not a closure)

**Checkpoint**: case_67 passes with both assignment and return chain scenarios validated end-to-end.

---

## Phase 5: User Story 3 — Chain emit in if-condition context (Priority: P3)

**Goal**: `if (this.label.empty?())` compiles correctly in a condition. Per plan.md, this is **out of scope** for this fix — the if-condition context requires a separate investigation.

> **DEFERRED**: `IfConditionContext` chain support (FR-003) is P3 in spec.md but explicitly marked out of scope in plan.md. Do not implement in this branch.

- [X] T015 [US3] Add a `## Deferred` section to `specs/007-fix-dot-resolver/plan.md` documenting that `IfConditionContext` chain support (FR-003) is deferred to a future spec, and why (requires separate investigation per plan.md research)

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Full regression validation and safety check on the unchanged `wrapAsIIFE` path.

- [X] T016 [P] Run `php bin/stretch --mode=success` over all existing cases (case_1 through case_66) and confirm zero new failures
- [X] T017 [P] Run `php bin/stretch --mode=warning` and `php bin/stretch --mode=error` to confirm no regressions in those modes
- [X] T018 Review all remaining call sites of `wrapAsIIFE` in `FunctionEmitter.php` (SafeNavigation path) and confirm they still produce valid PHP — `wrapAsIIFE` must NOT have been deleted or modified

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — can start immediately
- **Foundational (Phase 2)**: Depends on Setup — BLOCKS all implementation
- **US1 (Phase 3)**: Depends on Foundational — MVP deliverable; T007 and T008 can run in parallel, T009 depends on T008
- **US2 (Phase 4)**: Depends on Phase 3 completion (extends case_67 files already created)
- **US3 (Phase 5)**: Independent of US1/US2 — documentation-only, can run any time after Foundational
- **Polish (Phase 6)**: Depends on Phase 3 and Phase 4 completion

### Within Each User Story

- T007 (create `.ps`) and T008 (add emitter helper) can run in parallel — different files
- T009 (update `overrideSelf`) depends on T008 being complete
- T010 (CaseValidation) depends on T007 being complete
- T011 (run orchestrator) depends on T009 and T010

### Parallel Opportunities

- T001, T002, T003 — all reads, fully parallel
- T004, T005, T006 — can run in parallel
- T007, T008 — different files, parallel
- T016, T017 — different orchestrator modes, parallel

---

## Parallel Example: User Story 1

```bash
# Can launch in parallel:
Task T007: Create samples/success/case_67/ChainAssignment.ps
Task T008: Add emitChainedExpression() to FunctionEmitter.php

# Then sequentially:
Task T009: Update overrideSelf() to use emitChainedExpression
Task T010: Create CaseValidation.php
Task T011: php bin/stretch --mode=success
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup (read the emitter)
2. Complete Phase 2: Foundational (trace the exact bug line)
3. Complete Phase 3: US1 — fix assignment chain emit + create case_67
4. **STOP and VALIDATE**: `php bin/stretch --mode=success` passes case_67
5. Proceed to US2 only after US1 is green

### Incremental Delivery

1. Setup + Foundational → root cause confirmed at exact line
2. US1 → assignment chain compiles cleanly → orchestrator passes case_67
3. US2 → return chain covered → orchestrator still passes
4. US3 → deferred, documented in plan.md
5. Polish → full regression over all modes confirms zero regressions

---

## Notes

- [P] tasks = different files, no dependencies — safe to run concurrently
- [Story] label maps each task to its user story for traceability
- `wrapAsIIFE` must NOT be deleted — the SafeNavigation path still uses it
- The fix is contained entirely in `phirescript/src/Compiler/Emitter/Declarations/FunctionEmitter.php` — no Parser, Resolver, Context, or ContextManager changes are required for US1 and US2
- Token advance rule is not applicable (emitter-only change)
- All sandbox cases must use `pkg PHireScript.SamplesN` where N equals the case folder number
