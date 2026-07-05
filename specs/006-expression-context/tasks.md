# Tasks: Unified Expression Context

**Input**: Design documents from `specs/006-expression-context/`

**Organization**: Tasks grouped by user story — each phase is independently testable.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no shared dependencies)
- **[Story]**: Which user story this task belongs to

---

## Phase 1: Setup

**Purpose**: Create sandbox case directories para os casos de validação.

- [x] T001 Create sandbox case directories `samples/success/case_61/` through `samples/success/case_65/`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Scanner change, rename + extend BinaryExpressionResolver, create all new AST nodes/resolvers/emitters, fill ExpressionContext. Tudo aqui bloqueia todas as user stories.

**⚠️ CRITICAL**: No user story work can begin until T002–T010 are complete.

- [x] T002 [P] Add `**` to `T_MODIFIER` pattern in `phirescript/src/Compiler/Scanner.php` — insert `\*\*` as the first alternative (before `\->`) so it is matched before `T_SYMBOL` can consume a single `*`
- [x] T003 Rename `ComparisonExpressionResolver.php` → `BinaryExpressionResolver.php` in `phirescript/src/Compiler/Parser/Ast/Resolver/Expressions/` — rename the file, rename the class, and extend `OPERATORS` constant to include `'+', '-', '*', '/', '%', '**'` alongside the existing comparison and logical operators
- [x] T004 Update all 23 `use` and `new ComparisonExpressionResolver` call sites across the 11 context files (`AssignmentContext`, `BinaryExpressionContext`, `ElseScopeContext`, `TryScopeContext`, `AlwaysScopeContext`, `ElseIfScopeContext`, `IfConditionContext`, `HandleScopeContext`, `ReturnContext`, `MethodScopeContext`, `IfScopeContext`) to reference `BinaryExpressionResolver` — depends on T003
- [x] T005 [P] Create `UnaryExpressionNode` in `phirescript/src/Compiler/Parser/Ast/Nodes/Expressions/UnaryExpressionNode.php` — fields: `string $operator` (`'!'` or `'-'`) and `?Node $operand = null`; extends `Expression`
- [x] T006 Create `UnaryExpressionEmitter` in `phirescript/src/Compiler/Emitter/Expressions/UnaryExpressionEmitter.php` — `emit()` returns `"{$node->operator}{$operand}"` where operand is recursively emitted; register alongside `BinaryExpressionEmitter` in `phirescript/src/Compiler/Emitter.php` — depends on T005
- [x] T007 [P] Create `UnaryNegationResolver` in `phirescript/src/Compiler/Parser/Ast/Resolver/Expressions/UnaryNegationResolver.php` — `isTheCase`: token is `!` or `-` AND context children are empty OR last child is an operator node; `resolve`: creates `UnaryExpressionNode`, enters nested `ExpressionContext` to fill the operand
- [x] T008 [P] Create `ParenGroupResolver` in `phirescript/src/Compiler/Parser/Ast/Resolver/Expressions/ParenGroupResolver.php` — `isTheCase`: token is `(`; `resolve`: increments `$context->parenDepth` and adds `'('` string fragment to the output stream
- [x] T009 Fill `ExpressionContext` in `phirescript/src/Compiler/Parser/Ast/Context/Expressions/ExpressionContext.php` — add `public int $parenDepth = 0`; populate the full resolver list (value producers, variable/this, method chains, `BinaryExpressionResolver`, `UnaryNegationResolver`, `ParenGroupResolver`, casting, structural); implement `canClose()` with paren-depth logic (do not close on EOL when `parenDepth > 0`; close on `)` that brings depth to 0; close on EOL at depth 0 unless next token is `.` or `?.`); implement `afterClose()` to add result node to parent context — depends on T003, T005, T007, T008
- [x] T010 Update `BinaryExpressionContext` in `phirescript/src/Compiler/Parser/Ast/Context/Expressions/BinaryExpressionContext.php` — add `'**'` and arithmetic operators (`+`, `-`, `*`, `/`, `%`) to the `canClose()` "do not close" operator list; merge `LOGICAL_OPERATORS` / `COMPARISON_OPERATORS` into a single `BINARY_OPERATORS` constant — depends on T003

**Checkpoint**: `ExpressionContext` está funcional. `BinaryExpressionResolver` reconhece operadores aritméticos. Nodes, resolvers e emitters de negação unária existem. Executar `composer quality` inside `phirescript/` deve passar sem erros.

---

## Phase 3: User Story 1 + 2 — Arithmetic in Assignments and Return (Priority: P1) 🎯 MVP

**Goal**: Arithmetic operators work in assignment RHS and return statements; `case_61` passes.

**Independent Test**: Compile `samples/success/case_61/` with `result = price * 1.1` and `return this.price * 0.9` inside a class; verify PHP output contains `$result = $price * 1.1` and `return $this->price * 0.9;`.

### Implementation

- [x] T011 Simplify `AssignmentContext` in `phirescript/src/Compiler/Parser/Ast/Context/Expressions/AssignmentContext.php` — after `=` is consumed, enter `ExpressionContext` for the RHS; set `node->right` from `ExpressionContext.afterClose()` result; remove arithmetic resolver duplication — depends on T009
- [x] T012 Simplify `ReturnContext` in `phirescript/src/Compiler/Parser/Ast/Context/Statements/ReturnContext.php` — retain only `EndOfLineResolver` and `CommentResolver` for structural close; after `return` keyword, delegate to `ExpressionContext`; set the return expression from `ExpressionContext.afterClose()` — depends on T009
- [x] T013 [P] [US1] Create `samples/success/case_61/Arithmetic.ps` — a class with methods exercising all arithmetic operators in assignments (`+`, `-`, `*`, `/`, `%`, `**`) and in return statements; package `pkg PHireScript.Samples61`
- [x] T014 [US1] Create `samples/success/case_61/CaseValidation.php` — assert each arithmetic operator appears correctly in the compiled PHP for both assignment and return contexts

**Checkpoint**: `php bin/stretch --mode=success` — case_61 must pass.

---

## Phase 4: User Story 3 — Grouped Expressions with Parentheses (Priority: P1)

**Goal**: `(a + b) * c` and multi-line `(...)` expressions compile correctly; `case_62` passes.

**Independent Test**: Compile `(a + b) * c` and a 3-line expression wrapped in outer `(...)` and verify PHP output preserves all parens and produces a single statement.

*No new compiler code needed — `ExpressionContext` paren-depth logic (T009) and `ParenGroupResolver` (T008) already cover this. Phase is sandbox validation only.*

- [x] T015 [P] [US3] Create `samples/success/case_62/GroupedExpressions.ps` — class with assignments using `(a + b) * c`, deeply nested parens `((a * b) + (c / d))`, and a multi-line expression spanning 3+ lines wrapped in outer `(`...`)`; package `pkg PHireScript.Samples62`
- [x] T016 [US3] Create `samples/success/case_62/CaseValidation.php` — assert grouped sub-expressions preserve parens in output and multi-line expression emits as a single PHP statement

**Checkpoint**: `php bin/stretch --mode=success` — case_62 must pass.

---

## Phase 5: User Story 4 — Unary Negation Operators (Priority: P1)

**Goal**: `!flag` and `-count` compile correctly; `case_63` passes.

**Independent Test**: Compile `isActive = !flag` and `opposite = -count` and verify PHP output is `$isActive = !$flag` and `$opposite = -$count`.

*No new compiler code needed — `UnaryNegationResolver` (T007), `UnaryExpressionNode` (T005), and `UnaryExpressionEmitter` (T006) already cover this. Phase is sandbox validation only.*

- [x] T017 [P] [US4] Create `samples/success/case_63/UnaryNegation.ps` — class exercising `!flag`, `-count`, `!this.isActive()`, and `-(price * 2)`; package `pkg PHireScript.Samples63`
- [x] T018 [US4] Create `samples/success/case_63/CaseValidation.php` — assert `!$flag`, `-$count`, `!$this->isActive()`, and `-($price * 2)` appear in compiled PHP

**Checkpoint**: `php bin/stretch --mode=success` — case_63 must pass.

---

## Phase 6: User Story 5 — Method Calls as Operands (Priority: P2)

**Goal**: `this.getCount() * 10` and similar method-chain operands compile correctly.

**⚠️ DEPENDENCY**: US5 depends on **BB-3** (DotResolver focus propagation) being resolved. If BB-3 is not resolved, create `case_64` with a `CaseValidation.php` that marks the case as pending/skipped.

- [ ] T019 [P] [US5] Create `samples/success/case_64/MethodCallOperands.ps` — class with `total = this.getBase() * this.getRate()`, `result = price.multipliedBy(rate) + fee`, `grouped = (this.getBase() + offset) * multiplier`; package `pkg PHireScript.Samples64`
- [ ] T020 [US5] Create `samples/success/case_64/CaseValidation.php` — assert `$this->getBase() * $this->getRate()`, `$fee`, and grouped chain with multiplication appear in compiled PHP; note BB-3 dependency in a comment if case is deferred

**Checkpoint**: `php bin/stretch --mode=success` — case_64 passes if BB-3 is resolved; otherwise deferred.

---

## Phase 7: User Story 6 — Math TypeMethods on Float and Int (Priority: P2)

**Goal**: `.root(n)`, `.log()`, `.log(base)` on Float and Int; `.round()`, `.floor()`, `.ceil()` on Int compile correctly.

**Independent Test**: Compile `r = x.root(3)`, `l = value.log()`, `lb = value.log(2)`, `n = count.round()` and verify `$x ** (1.0 / 3)`, `\log($value)`, `\log($value, 2)`, `\round($count)` in PHP output.

- [x] T021 [P] [US6] Add `root(n)`, `log()`, `log(base)` methods to `FloatMethods` in `phirescript/src/Runtime/DefaultOverrideMethods/Types/FloatMethods.php` — `root`: `@self ** (1.0 / @n)` → Float; `log`: `\log(@self)` → Float; `log(base)`: `\log(@self, @base)` → Float (optional `@base` param pattern)
- [x] T022 [P] [US6] Add `root(n)`, `log()`, `log(base)`, `round()`, `floor()`, `ceil()` to `IntMethods` in `phirescript/src/Runtime/DefaultOverrideMethods/Types/IntMethods.php` — same `root`/`log` as Float; `round`: `\round(@self)` → Int; `floor`: `\floor(@self)` → Int; `ceil`: `\ceil(@self)` → Int
- [x] T023 [P] [US6] Create `samples/success/case_65/MathTypeMethods.ps` — exercises `x.root(3)`, `value.log()`, `value.log(2)`, `count.round()`, `count.floor()`, `count.ceil()` on typed Float and Int variables; package `pkg PHireScript.Samples65`
- [x] T024 [US6] Create `samples/success/case_65/CaseValidation.php` — assert each PHP expansion appears in compiled output

**Checkpoint**: `php bin/stretch --mode=success` — case_65 must pass.

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: Quality gates, regression validation, documentation.

- [x] T025 Run `composer quality` inside `phirescript/` and fix any PSR-12, PHPStan, or Rector violations in all modified/created files: `Scanner.php`, `BinaryExpressionResolver.php`, `UnaryExpressionNode.php`, `UnaryExpressionEmitter.php`, `UnaryNegationResolver.php`, `ParenGroupResolver.php`, `ExpressionContext.php`, `BinaryExpressionContext.php`, `AssignmentContext.php`, `ReturnContext.php`, `Emitter.php`, `FloatMethods.php`, `IntMethods.php`
- [x] T026 Run `php bin/stretch --mode=success` from sandbox root and confirm all cases 1–65 pass with no regressions
- [x] T027 [P] Update `phirescript/CLAUDE.md` Language Feature Status — move "Arithmetic / logical expressions" (or equivalent entry) from Partial/Missing to Functional; add sandbox cases 61–65 reference; note US5 (case_64) dependency on BB-3

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 (Setup)**: No dependencies — start immediately
- **Phase 2 (Foundational)**: Depends on Phase 1 — blocks all user stories
  - T002 [P] with T003 (different files)
  - T003 → T004 (call sites depend on rename being done)
  - T005 → T006 (emitter depends on node)
  - T007 [P] and T008 [P] (independent new files)
  - T009 depends on T003, T005, T007, T008
  - T010 depends on T003
- **Phase 3 (US1+US2)**: Depends on Phase 2 — T011 and T012 can run in parallel; T013 [P] with T011/T012
- **Phase 4 (US3)**: Depends on Phase 2 — sandbox only; T015 [P] with Phase 3 work
- **Phase 5 (US4)**: Depends on Phase 2 — sandbox only; T017 [P] with Phase 3/4 work
- **Phase 6 (US5)**: Depends on Phase 2 + BB-3 external resolution
- **Phase 7 (US6)**: Depends on Phase 1 — T021 and T022 [P] are independent TypeMethods files; can start after Phase 2 checkpoint
- **Phase 8 (Polish)**: Depends on all desired user story phases

### User Story Dependencies

- **US1 + US2 (P1)**: Requires full Phase 2 — central stories
- **US3 (P1)**: Requires Phase 2 (ExpressionContext paren logic) — sandbox-only validation
- **US4 (P1)**: Requires Phase 2 (UnaryNegationResolver) — sandbox-only validation
- **US5 (P2)**: Requires Phase 2 + external BB-3 fix
- **US6 (P2)**: Requires Phase 1 and TypeMethods files only — largely independent

### Parallel Opportunities

```bash
# Phase 2 — run in parallel:
T002  # Scanner (independent file)
T003  # BinaryExpressionResolver rename (independent file)
T005  # UnaryExpressionNode (new file)
T007  # UnaryNegationResolver (new file)
T008  # ParenGroupResolver (new file)
# then after T003:
T004  # 23 call site updates
T010  # BinaryExpressionContext update
# then after T005:
T006  # UnaryExpressionEmitter
# then after T003 + T005 + T007 + T008:
T009  # ExpressionContext (depends on all above)

# Phase 3 — run in parallel after T009:
T011  # AssignmentContext delegation
T012  # ReturnContext delegation
T013  # case_61 .ps file

# Phases 4, 5, 7 — run in parallel after Phase 2:
T015  # case_62 .ps
T017  # case_63 .ps
T021  # FloatMethods
T022  # IntMethods
```

---

## Implementation Strategy

### MVP (US1 + US2 only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational — **blocks everything**
3. Complete Phase 3: AssignmentContext + ReturnContext delegation + case_61
4. **STOP and VALIDATE**: `php bin/stretch --mode=success` — case_61 must pass
5. Ship as unblocking change for the codebase

### Full Delivery

1. MVP above
2. Phase 4 (US3 — paren grouping) + Phase 5 (US4 — unary negation) in parallel — sandbox only
3. Phase 7 (US6 — TypeMethods) — independent, can overlap with any phase
4. Phase 6 (US5 — method call operands) — deferred until BB-3 is resolved
5. Phase 8 (Polish) — after all desired stories pass

---

## Notes

- The rename `ComparisonExpressionResolver` → `BinaryExpressionResolver` (T003 + T004) is purely mechanical — no logic change, only identifier updates. PHPStan will catch any missed reference during T025.
- `ExpressionContext` skeleton already exists in the codebase (empty class) — T009 fills it, not creates it.
- US3 and US4 have no compiler code in their story phases because `ExpressionContext` (T009) + the new resolvers (T007, T008) handle them entirely. The story phases are sandbox validation only.
- `root(0)` — division by zero in exponent emitted as-is; PHP raises the error at runtime. No compile-time guard needed per spec assumption.
- Sandbox case packages: `PHireScript.Samples61` through `PHireScript.Samples65`.
- If BB-3 is not resolved by the time US5 is attempted, create `case_64` with a note in `CaseValidation.php` and defer the assertion.
