# Research: Named Parameters in Method Calls (010)

**Date**: 2026-07-09

## Finding 1 — `isColon()` already exists on `Token`

`Token::isColon()` returns `true` when `$this->value === ':'`. No scanner change needed. The colon is already emitted as a symbol token in the existing scanner.

**Implication**: `NamedArgResolver.isTheCase()` can use `$parseContext->tokenManager->getNextTokenAfterCurrent()->isColon()` directly.

## Finding 2 — `VariableReferenceResolver` collision risk

`VariableReferenceResolver.isTheCase()` matches `identifier AND variable-in-scope`. In a call like `getCsv(separator: ',')`, `separator` is unlikely to be a declared variable — but it *could* be. `NamedArgResolver` must be registered before `VariableReferenceResolver` in `ParamsConsumptionContext` and its `isTheCase` must also check `next-token-is-colon` to distinguish.

## Finding 3 — `BaseParams::name` uses `@` prefix

All `BaseParams` names in `StringMethods.php` (and other TypeMethods files) use `@` prefix: `@separator`, `@enclosure`, `@escape`. PHireScript source does not use `@`. During named-arg lookup, the emitter must strip the `@` prefix from `BaseParams::name` before comparison.

Confirmed in `getCsv`:
```php
new BaseParams('@separator', 'string', false, ','),
new BaseParams('@enclosure', 'string', false, "\""),
new BaseParams('@escape', 'string', false, "\\"),
```

## Finding 4 — `normalizeParams` is the correct validation point

`FunctionEmitter::normalizeParams()` is called for every `FunctionNode` emit, has access to both `$sentParams` (the child nodes from `ParamsNode`) and `$expected` (the `BaseParams[]` array from `BaseMethods`). This is the correct place to detect mixed style, unknown names, missing required args, and duplicates. All errors should throw `CompileException` using the `FunctionNode::$token` position.

## Finding 5 — `NamedArgContext` closing constraint

`NamedArgContext` must close after collecting exactly one value, and it must NOT consume the comma or closing parenthesis. Those belong to the parent `ParamsConsumptionContext`. The safe close condition: after one value child has been added, `canClose()` returns `true` for `,` and `)`. The parent context then handles separation and closure.

## Finding 6 — No `IgnoreColonResolver` in `ParamsConsumptionContext` today

`ParamsConsumptionContext` does not currently register `IgnoreColonResolver`. This means a colon token arriving there today would throw `CompileException("... is not supported in params context!")`. After this feature, the colon is consumed by `NamedArgContext` as the first token after the identifier. The colon never reaches the parent context's resolver loop, so no `IgnoreColonResolver` needs to be added to `ParamsConsumptionContext`.

## Finding 7 — Good sandbox methods for testing

| Method | Required params | Optional params | Notes |
|--------|----------------|----------------|-------|
| `getCsv` | 0 | 3 (`@separator`, `@enclosure`, `@escape`) | All optional — good for reorder test |
| `split` | 1 (`@separator`) | 1 (`@limit`) | Mix required+optional |
| `repeat` | 1 (`@times`) | 0 | Single required param |
| `padLeft`/`padRight` | 1 (`@length`) | 1 (`@pad`) | Good for missing required test |

## Finding 8 — Error case sandbox numbering

Last error case is `case_50`. New error cases: `case_51` through `case_54`.
Last success case is `case_73`. New success cases: `case_74` and `case_75`.
