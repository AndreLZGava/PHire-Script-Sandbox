# Tasks: Compiler Refactors + strict_types Output (009)

**Input**: Design documents from `/specs/009-refactors-strict-types/`

**Branch**: `009-refactors-strict-types` | **Date**: 2026-07-09

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2)
- Exact file paths included in every task

---

## Phase 1: Setup

**Purpose**: Create the branch and confirm the compiler baseline passes.

- [X] T001 Create branch `009-refactors-strict-types` off current HEAD and confirm `php bin/stretch --mode=success` passes before any changes

---

## Phase 2: Foundational

No foundational prerequisites beyond Setup. Both user stories touch different files and are independent.

**Checkpoint**: Branch is ready — US1 and US2 can proceed in parallel.

---

## Phase 3: User Story 1 — TD-11: Named constant in TokenManager (P1)

**Goal**: Replace magic number `100` with `DEFAULT_TOKEN_WINDOW` in `phirescript/src/Compiler/Parser/Managers/TokenManager.php`.

**Independent Test**: `php bin/stretch --mode=success` still passes; inspect the diff to confirm `100` no longer appears as a bare literal in the two default-parameter positions.

### Implementation for User Story 1

- [X] T002 [US1] Add `private const DEFAULT_TOKEN_WINDOW = 100;` to class body of `phirescript/src/Compiler/Parser/Managers/TokenManager.php`
- [X] T003 [US1] Replace default value `100` in `getLeftTokens(int $limit = 100)` with `self::DEFAULT_TOKEN_WINDOW` in `phirescript/src/Compiler/Parser/Managers/TokenManager.php:34`
- [X] T004 [US1] Replace default value `100` in `getProcessedTokens(int $limit = 100)` with `self::DEFAULT_TOKEN_WINDOW` in `phirescript/src/Compiler/Parser/Managers/TokenManager.php:39`
- [X] T005 [US1] Verify `getNextAfterFirstFoundElement()` still uses the literal `1000` (intentionally different window) — no change needed in `phirescript/src/Compiler/Parser/Managers/TokenManager.php:60`
- [X] T006 [US1] Run `php bin/stretch --mode=success` and confirm all cases pass
- [X] T007 [US1] Mark TD-11 as closed in `agents/pm/backlog.md`

**Checkpoint**: TD-11 complete. `TokenManager` constant in place, all existing cases green.

---

## Phase 4: User Story 2 — strict_types in generated PHP (P1)

**Goal**: Every `.php` file emitted by PHireScript starts with `<?php` + blank line + `declare(strict_types=1);`.

**Independent Test**: Compile any `.ps` file and inspect the output — line 3 must be `declare(strict_types=1);`. All `.psc` snapshots match regenerated output. `php bin/stretch --mode=success` passes.

### Implementation for User Story 2

- [X] T008 [US2] Change `$code['init'] = "<?php\n\n";` to `$code['init'] = "<?php\n\ndeclare(strict_types=1);\n\n";` in `phirescript/src/Compiler/Emitter/Root/ProgramEmitter.php:24`
- [X] T009 [US2] Point `PHireScript.json` `source` to `samples/success/case_1` (or any single case) and run `php phirescript/bin/build` to visually confirm the output header is correct — do NOT commit the `PHireScript.json` change
- [X] T010 [US2] Run `php bin/stretch --mode=success` and note any sandbox case failures caused by latent type mismatches surfaced by `strict_types=1`
- [X] T011 [US2] Fix any sandbox case that fails due to a type mismatch (fix the `.ps` source or the `CaseValidation.php` assertion, not the emitter); repeat for all failing cases (no failures — all cases passed cleanly)
- [X] T012 [US2] Regenerate all `.psc` snapshot files by running `php phirescript/bin/snapshot samples/success/ src/compiled/` — 80 snapshots updated
- [X] T013 [US2] Run `php bin/stretch --mode=success` and confirm all cases pass with the new `.psc` snapshots
- [X] T014 [P] [US2] Create sandbox case `samples/success/case_73/` with `Greeter.ps`, `Greeter.psc`, `CaseValidation.php`, `GreeterTest.php` — validates `strict_types=1` header in generated PHP

**Checkpoint**: US2 complete. All generated PHP files now include `declare(strict_types=1);`, snapshots updated, new case validates the header.

---

## Phase 5: Polish

- [X] T015 [P] Restore `PHireScript.json` to `"source": "src\/output"` — already restored by orchestrator pattern
- [X] T016 Run `php bin/stretch --mode=success,warning,error` (all modes) to confirm no regressions — all modes pass
- [X] T017 [P] Mark `strict_types output` as complete — TD-8 and TD-11 closed in `agents/pm/backlog.md`

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately
- **US1 (Phase 3)** and **US2 (Phase 4)**: Both depend only on Phase 1; they touch different files and can proceed in parallel
- **Polish (Phase 5)**: Depends on US1 and US2 both being complete

### User Story Dependencies

- **US1 (TD-11)**: Depends on Phase 1 only. Touches `TokenManager.php` exclusively.
- **US2 (strict_types)**: Depends on Phase 1 only. Touches `ProgramEmitter.php`, `.psc` snapshots, and potentially sandbox case `.ps` files.

### Parallel Opportunities

- US1 and US2 can run simultaneously — no shared files.
- T002, T003, T004 within US1 can all be done sequentially in one edit session (same file).
- T015 and T017 in Polish can run in parallel.

---

## Parallel Example: US1 and US2 together

```
Agent A: Complete Phase 3 (US1 — TokenManager, T002–T007)
Agent B: Complete Phase 4 (US2 — ProgramEmitter + snapshots, T008–T014)
Both done → Polish (Phase 5, T015–T017)
```

---

## Implementation Strategy

### Suggested order (solo developer)

1. **T001** — Create branch, run baseline
2. **T002–T007** — US1 (TD-11, ~10 minutes, very low risk)
3. **T008–T014** — US2 (strict_types, moderate effort due to snapshot regeneration)
4. **T015–T017** — Polish

### Key risks

- **Snapshot regeneration (T012)**: `PHireScript.json` must point to `samples/` (not a single case) for a full regeneration. After regeneration, restore to `"source": "samples"`.
- **Latent type mismatches (T010–T011)**: Some sandbox cases may have relied on PHP's implicit coercions. `strict_types=1` will surface these as `TypeError`. Fix the source, not the flag.
- **`getNextAfterFirstFoundElement` (T005)**: Must remain `1000`, not `DEFAULT_TOKEN_WINDOW`. Verify explicitly.

---

## Notes

- [P] tasks = different files, no shared state
- US1 and US2 are fully independent — implement in any order or in parallel
- Do not commit `PHireScript.json` changes (source path changes are always local-only per project convention)
- `declare(strict_types=1)` must appear before `namespace` in PHP — the current `processEntireCode()` ordering in `ProgramEmitter` already guarantees this (init first, then package/namespace)
- Total tasks: 17 (T001–T017)
