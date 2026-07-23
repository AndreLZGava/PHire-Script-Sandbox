# Implementation Plan: Named Parameters in Method Calls (P1-6)

**Branch**: `010-named-params` | **Date**: 2026-07-09 | **Spec**: [spec.md](spec.md)

## Summary

Allow PHireScript developers to call methods using named argument syntax (`param: value`) in addition to the existing positional syntax. When all arguments are named, the compiler resolves them by matching `BaseParams::name` and reorders them into the declared positional order before emission. Mixed positional+named calls are a compile-time error. The emitted PHP is always standard positional syntax, keeping output compatible with PHP 7.4+.

## Technical Context

**Language/Version**: PHP 8.1 (compiler), PHireScript (source language)

**Primary Dependencies**: PHireScript compiler pipeline — Scanner, Parser (TokenManager, Contexts, Resolvers), Emitter, Checker, Runtime `BaseParams`

**Storage**: N/A

**Testing**: PHPUnit via `php bin/stretch --mode=success` and `--mode=error`

**Target Platform**: PHireScript transpiler (CLI tool)

**Project Type**: Compiler / transpiler

**Performance Goals**: No impact — named arg resolution is done at compile time over a small, bounded param list.

**Constraints**: Zero scanner token changes. Zero regressions on existing positional calls. PHP output must be positional (PHP 7.4+ compatible).

**Scale/Scope**: Affects `ParamsConsumptionContext` (one context), `FunctionEmitter` (one emitter), plus one new node and one new resolver. Blast radius is small.

## Constitution Check

No constitution file is defined for this project. Evaluated against the PHireScript architectural invariants from `CLAUDE.md`:

| Gate | Status | Notes |
|------|--------|-------|
| Token advance rule | ✅ Pass | `NamedArgResolver` uses only `getNextTokenAfterCurrent()` (read-only). No `advance()` call. |
| Trinity completeness (Parser+Binder+Emitter) | ✅ Pass | All three layers covered: Parser produces `NamedArgNode`, Checker validates, Emitter resolves by name. |
| Blast radius | ✅ Pass | Isolated to `ParamsConsumptionContext`, `FunctionEmitter`, and one new node. No existing context logic is changed except additions. |
| Scanner purity | ✅ Pass | No new scanner token introduced. Colon is already scanned as a symbol token; detection happens at resolver level. |

## Project Structure

### Documentation (this feature)

```text
specs/010-named-params/
├── plan.md              ← this file
├── spec.md              ← feature specification
├── research.md          ← Phase 0 output (below)
├── data-model.md        ← Phase 1 output (below)
├── vscode-extension.md  ← extension guidance (below)
└── checklists/
    └── requirements.md
```

### Source Code — files to create or modify

```text
phirescript/src/Compiler/
├── Parser/
│   ├── Ast/
│   │   ├── Nodes/
│   │   │   └── Expressions/
│   │   │       └── NamedArgNode.php                      [NEW]
│   │   ├── Resolver/
│   │   │   └── Expressions/
│   │   │       └── NamedArgResolver.php                  [NEW]
│   │   └── Context/
│   │       └── Declarations/
│   │           └── ParamsConsumptionContext.php          [MODIFY — register NamedArgResolver]
├── Emitter/
│   └── Declarations/
│       └── FunctionEmitter.php                          [MODIFY — named→positional reorder]

PHire-Script-Sandbox/
└── samples/
    ├── success/
    │   ├── case_74/                                     [NEW — named args, reorder, all optional]
    │   └── case_75/                                     [NEW — named args on required params]
    └── error/
        ├── case_51/                                     [NEW — mixed positional+named → error]
        ├── case_52/                                     [NEW — unknown param name → error]
        ├── case_53/                                     [NEW — missing required named param → error]
        └── case_54/                                     [NEW — duplicate param name → error]
```

---

## Phase 0: Research

### Decision 1 — Detection point: Resolver vs Context

**Decision**: Detect `identifier :` inside `ParamsConsumptionContext` via a dedicated `NamedArgResolver`, registered *before* `VariableReferenceResolver` in the resolver list.

**Rationale**: `VariableReferenceResolver.isTheCase()` already matches `identifier` tokens when the identifier is a declared variable. A named arg like `separator: ','` could also be a variable name in scope. `NamedArgResolver` must fire first, checking `identifier AND next-token-is-colon`. This gives it priority over `VariableReferenceResolver`. No context restructuring needed.

**Alternatives considered**:
- Detect in `ParamsConsumptionContext.handle()` before the resolver loop — rejected because it bypasses the resolver pattern and mixes concerns into the context.
- Use a new scanner token `T_NAMED_ARG_SEPARATOR` — rejected per FR-009 and architectural principle (scanner must stay pure).

### Decision 2 — Node shape: NamedArgNode

**Decision**: `NamedArgNode` holds `string $paramName` and `Node $value`. It extends `Node`. The `$paramName` is the raw identifier string (not `@`-prefixed). The `$value` is any node that `ParamsConsumptionContext` would normally produce for a positional argument.

**Rationale**: Mirrors `KeyValuePairNode` pattern. The emitter needs only the name and value — no other metadata. The name will be matched against `BaseParams::name` (which is `@separator`, `@enclosure`, etc.). The resolver strips the `@` prefix from `BaseParams::name` during lookup.

**Alternatives considered**:
- Reuse `KeyValuePairNode` — rejected because its key is a `?Node` (a value node), while named arg keys are always plain identifier strings. Using it would require a cast or a `StringNode` wrapping a string token, muddying the AST.

### Decision 3 — Colon handling: does `NamedArgResolver` consume the colon?

**Decision**: Yes. `NamedArgResolver.resolve()` consumes the colon via `getNextTokenAfterCurrent()` read then opens a sub-context (or inline reads) to collect the value. The `IgnoreColonResolver` already in many contexts is *not* added to `ParamsConsumptionContext` — the colon after a named arg is handled exclusively by `NamedArgResolver`.

**Rationale**: The colon token must not fall through to an unhandled state. Since `NamedArgResolver` recognises `identifier + colon`, it owns the colon. After consuming the identifier and peeking the colon, the resolver advances past the colon and then re-enters the standard value-parsing flow for the value part. **Architectural note**: only `Parser.php` may call `advance()`. Therefore `NamedArgResolver.resolve()` must NOT advance the cursor itself. Instead, it creates a `NamedArgContext` that handles the colon token first (via an `IgnoreColonResolver`), then collects the value using the same resolvers as `ParamsConsumptionContext`.

**Revised approach**: `NamedArgResolver` creates a `NamedArgContext` and enters it. `NamedArgContext` handles the colon (ignores it) then one value token, then closes. The value node is attached to the `NamedArgNode`.

### Decision 4 — Validation layer: Checker vs Emitter

**Decision**: Validation of named arg rules (mixed style, unknown name, missing required, duplicate) is done in **`FunctionEmitter.normalizeParams()`**, not in `Checker.php`.

**Rationale**: `Checker` currently validates structural/semantic rules (return types, method existence). Argument-level validation at emit time is the established pattern for PHireScript — the emitter already calls `normalizeParams` for every `FunctionNode` and already has access to both `$sentParams` (the actual nodes) and `$expected` (the `BaseParams` list). Adding the mixed/unknown/missing/duplicate checks there keeps them co-located with the param-resolution logic and avoids a second traversal pass. Errors throw `CompileException` with file+line+column from the `FunctionNode::$token`.

**Alternatives considered**:
- Dedicated Checker pass — rejected because the Checker doesn't currently iterate over `FunctionNode.params` children and adding that traversal would be a larger change with more blast radius.

### Decision 5 — Mixed-style detection

**Decision**: After collecting all params from `FunctionNode.params->params`, `normalizeParams` checks: if any param is a `NamedArgNode` and any other is *not* a `NamedArgNode`, throw `CompileException("Cannot mix positional and named arguments in the same call")`.

**Rationale**: Simple and unambiguous. The check runs before any reordering logic.

### Decision 6 — Named→positional reorder algorithm

**Decision**: When all params are `NamedArgNode`, build a lookup `['separator' => $node, 'enclosure' => $node, ...]` from the sent params (stripping `@` from `BaseParams::name` for comparison). Then iterate `$expected` in declaration order, look up each by name, emit the value node. Missing required → `CompileException`. Missing optional → use `processDefaultValue()`. Unknown name (sent name not in any `BaseParams`) → `CompileException`.

**Rationale**: Produces positional PHP output regardless of the written order. Clean separation between lookup (by name) and emission (by position).

---

## Phase 1: Data Model

See [data-model.md](data-model.md).

---

## Implementation Steps

### Step 1 — New AST node: `NamedArgNode`

File: `phirescript/src/Compiler/Parser/Ast/Nodes/Expressions/NamedArgNode.php`

```php
class NamedArgNode extends Node {
    public function __construct(
        Token $token,
        public string $paramName,   // the identifier as written, e.g. "separator"
        public ?Node $value = null  // set after the value sub-context closes
    ) { parent::__construct($token); }
}
```

No emitter needed for `NamedArgNode` itself — the emitter reads `->value` and emits it.

---

### Step 2 — New context: `NamedArgContext`

File: `phirescript/src/Compiler/Parser/Ast/Context/Declarations/NamedArgContext.php`

Handles exactly two tokens: the colon (ignored) and then the value. Resolvers: `IgnoreColonResolver`, then the full value set from `ParamsConsumptionContext` minus `ClosingParamsConsumptionResolver` and `CommaResolver` (those belong to the parent). Closes when a value node has been added (`canClose` returns true after one value child).

On close, sets `$this->node->value = end($this->children)` and exits context.

---

### Step 3 — New resolver: `NamedArgResolver`

File: `phirescript/src/Compiler/Parser/Ast/Resolver/Expressions/NamedArgResolver.php`

```
isTheCase:  token->isIdentifier() && next-token->isColon()
resolve:    create NamedArgNode(paramName: token->value)
            enter NamedArgContext(namedArgNode)
            context->addChild(namedArgNode)
```

No cursor advance — `Parser.php` advances after `resolve()` returns, the `NamedArgContext` then handles the colon and value tokens.

---

### Step 4 — Register in `ParamsConsumptionContext`

Modify `phirescript/src/Compiler/Parser/Ast/Context/Declarations/ParamsConsumptionContext.php`:

Add `new NamedArgResolver()` at the **top** of the resolver list (before `ExternalClassAccessResolver` and `VariableReferenceResolver`). This ensures that `identifier + colon` is captured as a named arg before `VariableReferenceResolver` claims the identifier.

---

### Step 5 — Modify `FunctionEmitter.normalizeParams()`

Modify `phirescript/src/Compiler/Emitter/Declarations/FunctionEmitter.php`:

1. **Detect style**: check if any param `instanceof NamedArgNode`.
2. **Mixed check**: if mixed → `CompileException`.
3. **Named path**: build name-keyed lookup, strip `@` prefix from `BaseParams::name`. Iterate `$expected` in order:
   - Found by name → emit `$namedArgNode->value`
   - Not found + required → `CompileException("Missing required named argument: {name}")`
   - Not found + optional → `processDefaultValue()`
   - Extra names in sent params not matching any `BaseParams` → `CompileException("Unknown named argument: {name}")`
   - Duplicate names → detected during lookup map construction, `CompileException`
4. **Positional path**: unchanged.

---

### Step 6 — Sandbox cases

#### `samples/success/case_74/` — Named args, all optional, reordered

`NamedParamsBasic.phs`:
```
pkg PHireScript.Samples74

csv = 'hello,world'
result = csv.getCsv(enclosure: '"', separator: ',')
```

`CaseValidation.php`: asserts `✔ ... NamedParamsBasic.phs`

`NamedParamsBasicTest.php`: asserts emitted PHP contains `\str_getcsv($csv, ',', '"', ...)` (separator first = declared order).

#### `samples/success/case_75/` — Named args with required param (split)

`NamedParamsSplit.phs`:
```
pkg PHireScript.Samples75

text = 'a-b-c'
parts = text.split(separator: '-')
```

`CaseValidation.php`: asserts `✔ ... NamedParamsSplit.phs`

`NamedParamsSplitTest.php`: asserts PHP contains `\explode('-', $text, ...)`.

#### `samples/error/case_51/` — Mixed positional + named

`MixedArgs.phs`:
```
pkg PHireScript.Samples51

csv = 'hello,world'
result = csv.getCsv(',', enclosure: '"')
```

`CaseValidation.php`: asserts error message contains `Cannot mix positional and named arguments`.

#### `samples/error/case_52/` — Unknown parameter name

`UnknownArgName.phs`:
```
pkg PHireScript.Samples52

csv = 'hello,world'
result = csv.getCsv(badParam: ',')
```

`CaseValidation.php`: asserts error message contains `Unknown named argument`.

#### `samples/error/case_53/` — Missing required named param

`MissingRequiredArg.phs`:
```
pkg PHireScript.Samples53

text = 'a-b-c'
parts = text.split(limit: 3)
```

`CaseValidation.php`: asserts error message contains `Missing required named argument`.

#### `samples/error/case_54/` — Duplicate named param

`DuplicateArgName.phs`:
```
pkg PHireScript.Samples54

csv = 'hello,world'
result = csv.getCsv(separator: ',', separator: ';')
```

`CaseValidation.php`: asserts error message contains `Duplicate named argument`.

---

## Open Questions / Risks

| Risk | Impact | Mitigation |
|------|--------|-----------|
| `VariableReferenceResolver` claims `identifier` before `NamedArgResolver` if resolver order is wrong | Silent bug — named arg treated as variable reference | `NamedArgResolver` registered first in `ParamsConsumptionContext` resolver list |
| A param name in the source matches a variable in scope AND is a valid param name | Ambiguity | `NamedArgResolver` fires first (next-token-is-colon wins); positional path unchanged |
| `NamedArgContext` closing logic: when does it know the value is complete? | Wrong context exit | Value context closes on `,` or `)` token — same as the parent; NamedArgContext closes after one value child and passes those tokens back up |
| `BaseParams` names use `@` prefix (`@separator`) but PHireScript source does not | Lookup mismatch | Strip `@` from `BaseParams::name` during comparison in normalizeParams |
