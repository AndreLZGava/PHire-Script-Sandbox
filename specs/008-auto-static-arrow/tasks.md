# Tasks: Automatic `static` Inference on Arrow Functions

**Input**: Design documents from `specs/008-auto-static-arrow/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md

**Tests**: Included — sandbox `CaseValidation.php` + `.phc` snapshot files are the test mechanism for this project.

**Organization**: Tasks grouped by user story to enable independent implementation and testing.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2, US3)
- Exact file paths included in every description

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Confirm the single-file change target and add missing import — no new project structure needed.

- [x] T001 Add `use PHireScript\Compiler\Parser\Ast\Nodes\Expressions\ThisExpressionNode;` import to `phirescript/src/Compiler/Emitter/Declarations/ArrowFunctionEmitter.php` (line 11, after existing ArrowFunctionNode import)

**Checkpoint**: Import present — compilation of modified file will succeed.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Implement the `containsThisExpression()` detection method and update `emit()`. This single change is the foundation for all three user stories — no story can be verified until it lands.

**⚠️ CRITICAL**: No user story sandbox case can pass until this phase is complete.

- [x] T002 Add private method `containsThisExpression(array $nodes): bool` to `phirescript/src/Compiler/Emitter/Declarations/ArrowFunctionEmitter.php` — walk `MethodScopeNode.children`, return `true` on `ThisExpressionNode`, stop recursing at nested `ArrowFunctionNode` boundaries, recurse into `ReturnNode.expression` and `MethodScopeNode.children`
- [x] T003 Update `emit()` in `phirescript/src/Compiler/Emitter/Declarations/ArrowFunctionEmitter.php` — compute `$hasThis = $this->containsThisExpression($node->bodyCode?->children ?? [])` before building `$signature`; set `$signature = $hasThis ? ' function' : ' static function'`

**Checkpoint**: Compiler modified — both static and non-static paths are now wired.

---

## Phase 3: User Story 1 — Arrow without `this` emits `static function` (Priority: P1) 🎯 MVP

**Goal**: Verify that the compiler transparently adds `static` to every arrow function that does not reference `this`.

**Independent Test**: `php bin/stretch --mode=success` passes for case_68 and case_69.

### Implementation for User Story 1

- [x] T004 [US1] Update snapshot `samples/success/case_68/ArrowFunctionFloat.phc` — change `function (float $price, float $rate): float {` to `static function (float $price, float $rate): float {`
- [x] T005 [US1] Review `samples/success/case_68/ArrowFunctionFloatTest.php` — update any assertion strings that contain the literal `function` (without `static`) to include `static function`
- [x] T006 [US1] Create `samples/success/case_69/ArrowFunctionNoThis.phs` — package `PHireScript.Samples69`; declare one arrow function (e.g., `double = (Int n): Int => { return n }`) with no `this` reference
- [x] T007 [US1] Run `php phirescript/bin/snapshot` (with `PHireScript.json` source pointing to `samples/success/case_69`) to generate `samples/success/case_69/ArrowFunctionNoThis.phc`; verify snapshot contains `static function`
- [x] T008 [US1] Create `samples/success/case_69/ArrowFunctionNoThisTest.php` — `namespace PHireScript\Sandbox\src\output;` PHPUnit test asserting the compiled output is callable and returns correct values
- [x] T009 [US1] Create `samples/success/case_69/CaseValidation.php` — extend `AbstractCaseValidation`; assert compilation succeeds; use snapshot comparison

**Checkpoint**: `php bin/stretch --mode=success --tags=case_68,case_69` passes — arrows without `this` now receive `static function`.

---

## Phase 4: User Story 2 — Arrow with `this` stays as plain `function` (Priority: P1)

**Goal**: Verify that the `this`-detection gate correctly suppresses `static` when the arrow function body references `this`.

**Independent Test**: `php bin/stretch --mode=success` passes for case_70 and case_53 (existing regression guard).

### Implementation for User Story 2

- [x] T010 [US2] Create `samples/success/case_70/ArrowFunctionWithThis.phs` — package `PHireScript.Samples70`; declare a class with a method containing an arrow function that reads `this.someField` (e.g., a `Formatter` class with a `getFormatter()` method returning an arrow function that reads `this.prefix`)
- [x] T011 [US2] Run `php phirescript/bin/snapshot` (source pointing to `samples/success/case_70`) to generate `samples/success/case_70/ArrowFunctionWithThis.phc`; verify snapshot contains plain `function` (no `static` prefix)
- [x] T012 [US2] Create `samples/success/case_70/ArrowFunctionWithThisTest.php` — PHPUnit test asserting the class compiles and the method returns a callable
- [x] T013 [US2] Create `samples/success/case_70/CaseValidation.php` — extend `AbstractCaseValidation`; assert compilation succeeds; snapshot comparison confirms no `static`
- [x] T014 [P] [US2] Run `php bin/stretch --mode=success` and confirm case_53 (Mapper with `this.prefix`) still passes unchanged — this is the existing regression guard for the non-static path

**Checkpoint**: `php bin/stretch --mode=success` passes for cases 53 and 70 — `this`-using arrows are correctly kept non-static.

---

## Phase 5: User Story 3 — Backward Compatibility (Priority: P2)

**Goal**: Confirm every previously passing sandbox case still passes after the feature lands, with no `.phs` file changes required.

**Independent Test**: `php bin/stretch --mode=success` passes for all cases, including all arrow function cases (35, 36, 37, 38, 68, 69, 70).

### Implementation for User Story 3

- [x] T015 [US3] Run full `php bin/stretch --mode=success` and confirm zero regressions across all cases
- [x] T016 [P] [US3] For each arrow-function case that now emits `static function` (35, 36, 37, 38), verify or update their `.phc` snapshot files to include `static function` — run `php phirescript/bin/snapshot` per case as needed and update snapshots
- [x] T017 [P] [US3] For each snapshot-based `*Test.php` in cases 35–38, update any assertion strings that contained literal `function` to `static function` (same pattern as T005)

**Checkpoint**: `php bin/stretch --mode=success` green across all cases — full backward compatibility confirmed.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Clean up, document, and validate the full suite end-to-end.

- [x] T018 [P] Run `php bin/stretch` (all modes: success, warning, error) — confirm no cross-mode regressions
- [x] T019 Restore `PHireScript.json` `source` to `samples` (if it was pointed at a specific case during snapshot generation)
- [x] T020 [P] Update `samples/success/case_68/CaseValidation.php` snapshot assertion if it references the old `function` string directly (consistency check)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately
- **Foundational (Phase 2)**: Depends on T001 — **BLOCKS all user stories**
- **US1 (Phase 3)**: Depends on Phase 2 completion
- **US2 (Phase 4)**: Depends on Phase 2 completion; can run in parallel with US1
- **US3 (Phase 5)**: Depends on Phase 3 + Phase 4 completion
- **Polish (Phase 6)**: Depends on Phase 5 completion

### User Story Dependencies

- **User Story 1 (P1)**: Starts after Phase 2 — no dependency on US2
- **User Story 2 (P1)**: Starts after Phase 2 — no dependency on US1 (same emitter method, separate sandbox cases)
- **User Story 3 (P2)**: Depends on US1 + US2 being complete (full-suite run)

### Within Each User Story

- Snapshot generation (snapshot command) → before CaseValidation creation
- CaseValidation → before stretch run
- Stretch run → checkpoint gate

### Parallel Opportunities

- T006 and T010 (create `.phs` files for case_69 and case_70) — different files, parallel
- T007 and T011 (generate snapshots) — sequential per case, but cases are parallel
- T014 and T015 (regression guards) — parallel read-only checks
- T016 and T017 (snapshot/test updates for cases 35–38) — parallel across cases

---

## Parallel Example: User Stories 1 & 2 (after Phase 2)

```bash
# After T003 lands, launch US1 and US2 in parallel:

# US1 track:
Task T004: Update case_68 snapshot
Task T006: Create case_69 .phs source
Task T007: Generate case_69 snapshot
Task T008: Create case_69 Test
Task T009: Create case_69 CaseValidation

# US2 track (simultaneously):
Task T010: Create case_70 .phs source
Task T011: Generate case_70 snapshot
Task T012: Create case_70 Test
Task T013: Create case_70 CaseValidation
Task T014: Verify case_53 regression
```

---

## Implementation Strategy

### MVP First (User Stories 1 + 2 together — they are the two sides of the same gate)

1. Complete Phase 1: T001 (import)
2. Complete Phase 2: T002 + T003 (core emitter change)
3. Complete Phase 3: T004–T009 (case_68 update + case_69 new)
4. Complete Phase 4: T010–T014 (case_70 new + case_53 regression)
5. **STOP and VALIDATE**: `php bin/stretch --mode=success` — MVP verified

### Incremental Delivery

1. Phase 1 + 2 → Emitter changed
2. Phase 3 → `static function` path verified (US1 MVP)
3. Phase 4 → `function` path verified (US2 correctness gate)
4. Phase 5 → Full backward-compat sweep (US3)
5. Phase 6 → Polish and full-suite clean run

---

## Notes

- [P] tasks = different files, no dependencies on each other
- Snapshot files (`.phc`) are the canonical output contract — update them whenever emitter output changes
- `PHireScript.json` source must point at the correct case folder before running `bin/snapshot`; restore to `samples` afterwards
- case_53 is the critical regression guard for the non-static path — it must never gain `static`
- Do not modify any `.phs` source files in existing cases — backward compatibility means zero `.phs` changes required
