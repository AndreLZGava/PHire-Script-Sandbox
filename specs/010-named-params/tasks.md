# Tasks: Named Parameters in Method Calls (P1-6)

**Input**: Design documents from `specs/010-named-params/`

**Prerequisites**: plan.md ✅ spec.md ✅ research.md ✅ data-model.md ✅

**Organization**: Tasks are grouped by user story to enable independent implementation and testing.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies on incomplete tasks)
- **[Story]**: Which user story this task belongs to
- All file paths are relative to the repo root

---

## Phase 1: Foundational — New AST & Parser pieces

**Purpose**: Create the `NamedArgNode`, `NamedArgContext`, and `NamedArgResolver`. These three files have no dependencies on each other and block all user story phases.

**⚠️ CRITICAL**: Phases 2–5 cannot begin until this phase is complete.

- [X] T001 [P] Create `NamedArgNode` in `phirescript/src/Compiler/Parser/Ast/Nodes/Expressions/NamedArgNode.php` — fields: `string $paramName`, `?Node $value` (see data-model.md §New AST Node)
- [X] T00X [P] Create `NamedArgContext` in `phirescript/src/Compiler/Parser/Ast/Context/Declarations/NamedArgContext.php` — resolver list: `IgnoreColonResolver` first, then `StringLiteralResolver`, `NumberLiteralResolver`, `BoolLiteralResolver`, `ArrayLiteralResolver`, `VariableReferenceResolver`, `ExternalClassAccessResolver`, `ExternalMethodCallResolver`; `canClose()` returns true on `,` or `)`; `afterClose()` sets `$this->node->value = end($this->children)` and calls `contextManager->exit()`
- [X] T00X [P] Create `NamedArgResolver` in `phirescript/src/Compiler/Parser/Ast/Resolver/Expressions/NamedArgResolver.php` — `isTheCase`: `$token->isIdentifier() && $parseContext->tokenManager->getNextTokenAfterCurrent()->isColon()`; `resolve`: instantiate `NamedArgNode($token->value)`, enter `NamedArgContext($node)`, `$context->addChild($node)`; no `advance()` call (Parser advances)

**Checkpoint**: Three new files exist and are syntactically valid. Composer autoload resolves them.

---

## Phase 2: US1 — Positional-only call unchanged

**Goal**: Verify that registering `NamedArgResolver` in `ParamsConsumptionContext` does not break any existing positional call.

**Independent Test**: `php bin/stretch --mode=success` passes with zero regressions after T004.

- [X] T00X Register `NamedArgResolver` as the **first** resolver in `phirescript/src/Compiler/Parser/Ast/Context/Declarations/ParamsConsumptionContext.php` (before `ExternalClassAccessResolver`) — add `use` statement and prepend `new NamedArgResolver()` to the resolver array
- [X] T00X [US1] Run the full success suite: `php bin/stretch --mode=success` — confirm zero regressions; fix any breakage before proceeding

**Checkpoint**: All existing success cases still pass. Positional calls work exactly as before.

---

## Phase 3: US2 — Named-only call with reordered arguments

**Goal**: Named arg calls compile and the emitted PHP uses declaration-order positional syntax.

**Independent Test**: `php bin/stretch --mode=success --from=74 --to=75` passes.

- [X] T00X [US2] Extend `FunctionEmitter::normalizeParams()` in `phirescript/src/Compiler/Emitter/Declarations/FunctionEmitter.php`:
  1. Detect style: `$hasNamed = any param instanceof NamedArgNode`
  2. **Mixed check** (T007 depends on this too): if `$hasNamed && any non-NamedArgNode` → `throw new CompileException("Cannot mix positional and named arguments in the same call", $functionNode->line, $functionNode->column)`
  3. **Named path**: build `$sentMap = ['separator' => NamedArgNode, ...]` from sent params keyed by `paramName`; detect duplicates — if `count(unique keys) < count($sentMap)` → `CompileException("Duplicate named argument: {name}")`; iterate `$expected` in declaration order: strip `@` from `BaseParams::name` → lookup in `$sentMap`; if found → emit `$namedArgNode->value`; if not found + required → `CompileException("Missing required named argument: {normalizedName}")`; if not found + optional → `processDefaultValue($expected)`; after loop, check for unknown names (keys in `$sentMap` not matched) → `CompileException("Unknown named argument: {name}")`
  4. **Positional path**: unchanged (runs when `$hasNamed` is false)
- [X] T00X [P] [US2] Create sandbox case `samples/success/case_74/` — named args, all optional params, reordered:
  - `NamedParamsBasic.phs`: `pkg PHireScript.Samples74` + `csv = 'hello,world'` + `result = csv.getCsv(enclosure: '"', separator: ',')`
  - `CaseValidation.php`: `assertHasMessage(['✔ src/output/NamedParamsBasic.phs'])` in `execute()`; `executeTest()` loads compiled file and asserts output contains `\str_getcsv($csv, ',', '"'` (separator first = declaration order)
- [X] T00X [P] [US2] Generate `.phc` snapshot for case_74: set `PHireScript.json` source to `samples/success/case_74`, run `php phirescript/bin/snapshot`, restore `PHireScript.json` source to `samples`
- [X] T00X [P] [US2] Create sandbox case `samples/success/case_75/` — named arg for required param (`split`):
  - `NamedParamsSplit.phs`: `pkg PHireScript.Samples75` + `text = 'a-b-c'` + `parts = text.split(separator: '-')`
  - `CaseValidation.php`: asserts `✔ src/output/NamedParamsSplit.phs`; `executeTest()` asserts compiled PHP contains `\explode('-', $text`
- [X] T01X [P] [US2] Generate `.phc` snapshot for case_75 (same steps as T008, source = `samples/success/case_75`)

**Checkpoint**: `php bin/stretch --mode=success --from=74 --to=75` passes. Emitted PHP has args in declaration order regardless of written order.

---

## Phase 4: US3 — Mixed positional + named is rejected

**Goal**: `CompileException` is thrown with a clear message when positional and named args are mixed.

**Independent Test**: `php bin/stretch --mode=error` includes `case_51` and it passes.

- [X] T01X [US3] Create sandbox case `samples/error/case_51/` — mixed positional + named:
  - `MixedArgs.phs`: `pkg PHireScript.Samples51` + `csv = 'hello,world'` + `result = csv.getCsv(',', enclosure: '"')`
  - `CaseValidation.php`: `assertHasMessage(['Cannot mix positional and named arguments'])`

**Checkpoint**: `php bin/stretch --mode=error` passes for case_51.

---

## Phase 5: US4 — Missing required named argument is rejected

**Goal**: All remaining error conditions (unknown name, missing required, duplicate) are caught at compile time.

**Independent Test**: `php bin/stretch --mode=error` passes for cases 52, 53, 54.

- [X] T01X [P] [US4] Create sandbox case `samples/error/case_52/` — unknown parameter name:
  - `UnknownArgName.phs`: `pkg PHireScript.Samples52` + `csv = 'hello,world'` + `result = csv.getCsv(badParam: ',')`
  - `CaseValidation.php`: `assertHasMessage(['Unknown named argument'])`
- [X] T01X [P] [US4] Create sandbox case `samples/error/case_53/` — missing required param (`split` requires `separator`):
  - `MissingRequiredArg.phs`: `pkg PHireScript.Samples53` + `text = 'a-b-c'` + `parts = text.split(limit: 3)`
  - `CaseValidation.php`: `assertHasMessage(['Missing required named argument'])`
- [X] T01X [P] [US4] Create sandbox case `samples/error/case_54/` — duplicate param name:
  - `DuplicateArgName.phs`: `pkg PHireScript.Samples54` + `csv = 'hello,world'` + `result = csv.getCsv(separator: ',', separator: ';')`
  - `CaseValidation.php`: `assertHasMessage(['Duplicate named argument'])`

**Checkpoint**: `php bin/stretch --mode=error` passes for cases 51–54.

---

## Phase 6: Polish & Validation

**Purpose**: Full suite validation and backlog update.

- [X] T01X Run full success suite `php bin/stretch --mode=success` — confirm zero regressions (SC-004)
- [X] T01X Run full error suite `php bin/stretch --mode=error` — confirm all error cases pass
- [X] T01X Mark **P1-6** as resolved in `agents/pm/backlog.md` (same strikethrough pattern as other resolved items, note date 2026-07-09)

**Checkpoint**: All four success criteria (SC-001 through SC-004) verified.

---

## Dependencies & Execution Order

```
Phase 1 (T001, T002, T003) — parallel, no deps
    │
    ▼
Phase 2 (T004, T005) — sequential; T004 depends on Phase 1; T005 depends on T004
    │
    ▼
Phase 3 (T006–T010) — T006 depends on Phase 2; T007–T010 depend on T006 (can be parallel after T006)
    │
    ▼
Phase 4 (T011) — depends on T006 (error detection logic already in place)
Phase 5 (T012–T014) — parallel with each other; depend on T006
    │
    ▼
Phase 6 (T015–T017) — depends on Phases 3, 4, 5 all complete
```

### Parallel Opportunities

```bash
# Phase 1 — run all three in parallel:
Task T001: "Create NamedArgNode in phirescript/.../Nodes/Expressions/NamedArgNode.php"
Task T002: "Create NamedArgContext in phirescript/.../Context/Declarations/NamedArgContext.php"
Task T003: "Create NamedArgResolver in phirescript/.../Resolver/Expressions/NamedArgResolver.php"

# Phase 3 after T006 — run in parallel:
Task T007: "Create samples/success/case_74/ with NamedParamsBasic.phs"
Task T008: "Generate .phc snapshot for case_74"
Task T009: "Create samples/success/case_75/ with NamedParamsSplit.phs"
Task T010: "Generate .phc snapshot for case_75"

# Phase 5 — run all three in parallel:
Task T012: "Create samples/error/case_52/"
Task T013: "Create samples/error/case_53/"
Task T014: "Create samples/error/case_54/"
```

---

## Implementation Strategy

### MVP (User Stories 1 + 2 only — minimum to ship)

1. Phase 1: Create 3 new files (T001–T003)
2. Phase 2: Wire resolver + verify no regressions (T004–T005)
3. Phase 3: Implement named path in emitter + 2 success cases (T006–T010)
4. **STOP and VALIDATE**: named calls work, existing calls unbroken

### Full Feature

Continue with Phases 4–6 to add error detection (mixed, unknown, missing, duplicate) and pass the error suite.

---

## Notes

- `BaseParams::name` uses `@` prefix (e.g. `@separator`) — strip it before comparing with source-level param names
- `NamedArgResolver` **must** be first in `ParamsConsumptionContext` resolver list to prevent `VariableReferenceResolver` from claiming the identifier token
- No `advance()` call in any Resolver — only `Parser.php` may advance the cursor
- Do not commit `PHireScript.json` changes made during snapshot generation
- Error cases live in `samples/error/` and use `--mode=error` when running stretch
