# Implementation Plan: Automatic `static` Inference on Arrow Functions

**Branch**: `008-auto-static-arrow` | **Date**: 2026-07-09 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `specs/008-auto-static-arrow/spec.md`

## Summary

At emit time, inspect every `ArrowFunctionNode`'s body AST for the presence of `ThisExpressionNode`. If none is found in the function's own scope (not recursing into nested `ArrowFunctionNode` subtrees), prefix the emitted PHP closure with `static`. If `ThisExpressionNode` is present, emit a plain `function` as today. The change is confined to `ArrowFunctionEmitter.php` — no scanner, parser, resolver, context, or checker modifications are needed.

## Technical Context

**Language/Version**: PHP 8.1+ (compiler source); PHireScript `.ps` (language under test)

**Primary Dependencies**: PHireScript compiler at `phirescript/` — specifically:
- `phirescript/src/Compiler/Emitter/Declarations/ArrowFunctionEmitter.php` (only file changed)
- `PHireScript\Compiler\Parser\Ast\Nodes\Declarations\ArrowFunctionNode` (read-only)
- `PHireScript\Compiler\Parser\Ast\Nodes\Expressions\ThisExpressionNode` (detection target)
- `PHireScript\Compiler\Parser\Ast\Nodes\Scopes\MethodScopeNode` (body container)

**Storage**: N/A

**Testing**: PHPUnit via `php bin/stretch`; snapshot files (`.psc`) for output regression

**Target Platform**: PHP 8.1+ server

**Project Type**: Compiler / transpiler (emit phase)

**Performance Goals**: No degradation — AST walk is linear and mirrors the existing `collectExternalRefs` walk already performed per arrow function.

**Constraints**: Token-advance rule: only `Parser.php` may advance the cursor. This feature is emit-only — constraint not relevant here, but noted for compliance.

**Scale/Scope**: Affects every arrow function emitted by the compiler. All existing arrow-function sandbox cases (case_35, 36, 37, 38, 53, 68) must continue to pass.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

The project constitution file is a blank template — no project-specific gates are defined. Falling back to PHireScript's documented architectural invariants:

| Gate | Status | Notes |
|------|--------|-------|
| Token-advance rule (only `Parser.php` advances cursor) | PASS | Change is emit-only; cursor not touched |
| Trinity completeness (Node + Context + Emitter for new constructs) | PASS | No new construct; existing `ArrowFunctionNode` extended at emit |
| No scanner/parser/resolver changes for emit-only features | PASS | Confirmed: only `ArrowFunctionEmitter.php` changes |
| Existing cases must not regress | PASS (pre-check) | Validated by sandbox run after implementation |

## Project Structure

### Documentation (this feature)

```text
specs/008-auto-static-arrow/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 0 output (minimal — one existing node touched)
├── checklists/
│   └── requirements.md  # Spec quality checklist
└── tasks.md             # Phase 2 output (/speckit-tasks — not created here)
```

### Source Code (repository root)

```text
phirescript/src/Compiler/Emitter/Declarations/
└── ArrowFunctionEmitter.php        # ONLY file modified

samples/success/case_68/            # Existing case: update .psc snapshot + test
samples/success/case_69/            # New case: arrow without this  → static function
samples/success/case_70/            # New case: arrow with this     → plain function
```

**Structure Decision**: Single-file emit change. New sandbox cases follow the established pattern: `.ps` source + `.psc` snapshot + `*Test.php` + `CaseValidation.php`.

## Complexity Tracking

No constitution violations. No complexity justification required.

---

## Phase 0: Research

*All NEEDS CLARIFICATION items resolved from codebase inspection.*

### Decision Log

| Topic | Decision | Rationale |
|-------|----------|-----------|
| Detection scope | Walk only the direct `MethodScopeNode` children; stop at nested `ArrowFunctionNode` | Nested arrow functions are independent closures; their `this` usage does not bind to the outer closure |
| Detection target | `ThisExpressionNode` only | It is the sole AST node for `this`; no aliases or alternative representations exist in the compiler |
| Walk depth | Recursive within `MethodScopeNode.children`, stopping at `ArrowFunctionNode` boundaries | Mirrors `collectRefs` pattern already in the emitter |
| Existing `collectRefs` reuse | Write a separate private method `containsThisExpression()` | `collectRefs` is purpose-built for variable capture names; a clean boolean method is clearer and avoids coupling |
| `static` placement in PHP | `static function(...)` — before `function` keyword | PHP spec: `static` must precede `function` for static closures |
| `.psc` snapshots | Update `case_68` snapshot; add new snapshots for cases 69 and 70 | Snapshots are the canonical regression baseline for output correctness |

### Resolved Unknowns

**Q: Does `ThisExpressionNode` appear anywhere inside an arrow-function AST when the source is `this.prop`?**
A: Yes — confirmed by inspecting `ThisPropertyAccessResolver` which creates `ThisExpressionNode` as the head of a property access chain. The node is present as a direct child of `MethodScopeNode.children` or nested inside another expression node under that scope.

**Q: Are there any other node types that represent `this`?**
A: No. `ThisExpressionNode` is the only class; confirmed by grep across all `Nodes/` directories.

**Q: Does case_53 (Mapper with `this.prefix` inside arrow) currently emit without `static`?**
A: Yes — current emitter always emits `function` without `static`. After this change, case_53 must continue to emit plain `function` (the `this` detection must keep it non-static).

---

## Phase 1: Design

### Data Model

No new AST nodes or data structures. The existing model is sufficient:

```
ArrowFunctionNode
├── token: Token
├── bodyCode: ?MethodScopeNode
│   └── children: object[]        ← walk stops at ArrowFunctionNode boundaries
├── parameters: ?ParamsListNode
└── returnType: ?ReturnTypeNode

ThisExpressionNode extends Expression   ← detection target (instanceof check)
```

**Walk algorithm** (pseudocode):

```
containsThisExpression(nodes: object[]): bool
  for each node in nodes:
    if node instanceof ThisExpressionNode → return true
    if node instanceof ArrowFunctionNode  → skip (boundary: independent scope)
    if node has child collection          → recurse
  return false
```

Child collections to recurse into (mirrors existing `collectRefs` logic):
- `MethodScopeNode::$children`
- `ReturnNode::$expression` (wrapped in array)
- Any other container nodes discovered during implementation

### Emit Change (ArrowFunctionEmitter)

```php
// Before (line 24):
$signature = ' function';

// After:
$hasThis = $this->containsThisExpression($node->bodyCode?->children ?? []);
$signature = $hasThis ? ' function' : ' static function';
```

Private method added:

```php
private function containsThisExpression(array $nodes): bool
{
    foreach ($nodes as $child) {
        if ($child instanceof ThisExpressionNode) {
            return true;
        }
        if ($child instanceof ArrowFunctionNode) {
            continue; // independent scope boundary
        }
        if ($child instanceof ReturnNode && $child->expression !== null) {
            if ($this->containsThisExpression([$child->expression])) {
                return true;
            }
        }
        if ($child instanceof MethodScopeNode) {
            if ($this->containsThisExpression($child->children)) {
                return true;
            }
        }
    }
    return false;
}
```

### Sandbox Cases

**case_68** (existing) — update `.psc` snapshot to reflect `static function`:
- Current snapshot: `$calcTotal = function (float $price, float $rate): float {`
- New snapshot: `$calcTotal = static function (float $price, float $rate): float {`

**case_69** (new) — Arrow function without `this`: verifies `static function` emission
- `.ps` source: standalone arrow function referencing only a parameter
- `.psc` snapshot: `static function`
- `CaseValidation.php`: asserts success + snapshot matches

**case_70** (new) — Arrow function with `this` inside a class method: verifies plain `function` emission
- `.ps` source: class with method containing an arrow function that reads `this.someField`
- `.psc` snapshot: plain `function` (no `static`)
- `CaseValidation.php`: asserts success + snapshot matches

### Contracts

No external interfaces are changed. The PHireScript language surface is unchanged (no new syntax). The PHP output contract changes only in the sense that closures without `this` now gain a `static` prefix — this is a backward-compatible improvement in emitted code quality.

### Quickstart

**To implement this feature**:

1. Edit `phirescript/src/Compiler/Emitter/Declarations/ArrowFunctionEmitter.php`:
   - Add `use PHireScript\Compiler\Parser\Ast\Nodes\Declarations\ArrowFunctionNode;` if not present (check: it may already be auto-loaded)
   - Add `use PHireScript\Compiler\Parser\Ast\Nodes\Expressions\ThisExpressionNode;`
   - Add `containsThisExpression(array $nodes): bool` private method
   - Update `emit()` to compute `$hasThis` before building `$signature`

2. Update snapshot `samples/success/case_68/ArrowFunctionFloat.psc` — change `function` to `static function`

3. Update `samples/success/case_68/ArrowFunctionFloatTest.php` if it asserts the exact closure string

4. Add `samples/success/case_69/` — new case confirming `static function` for non-`this` arrow

5. Add `samples/success/case_70/` — new case confirming plain `function` for `this`-using arrow

6. Run `php bin/stretch --mode=success` — all cases must pass

**Verification command**:
```bash
php bin/stretch --mode=success
```

**Expected change in case_68 snapshot**:
```diff
-$calcTotal = function (float $price, float $rate): float {
+$calcTotal = static function (float $price, float $rate): float {
```
