# Research: Automatic `static` Inference on Arrow Functions

**Feature**: 008-auto-static-arrow | **Date**: 2026-07-09

## Findings

### 1. `ThisExpressionNode` is the sole AST representation of `this`

- **Decision**: Use `instanceof ThisExpressionNode` as the detection predicate.
- **Rationale**: Grep across all `phirescript/src/Compiler/Parser/Ast/Nodes/` confirms no aliases, subtypes, or parallel node types for `this`. The class is a leaf (no fields); its mere presence in the AST is the signal.
- **Alternatives considered**: Checking token values or string patterns — rejected because AST-level checks are more robust and do not depend on source text serialisation.

### 2. `MethodScopeNode.children` is a flat `object[]`

- **Decision**: Walk `children` with recursive descent, stopping at `ArrowFunctionNode` boundaries.
- **Rationale**: `MethodScopeNode` (the body of any arrow function) holds its statements as `children: object[]`. The existing `collectRefs` method in `ArrowFunctionEmitter` already performs this walk for variable capture detection. The new `containsThisExpression` method follows the same pattern.
- **Alternatives considered**: Single-pass walk of all descendants — rejected because it would incorrectly treat `this` inside a nested arrow function as belonging to the outer one (separate closure scope in PHP).

### 3. Nested `ArrowFunctionNode` is a scope boundary

- **Decision**: When `containsThisExpression` encounters a child `ArrowFunctionNode`, it skips it entirely (does not recurse).
- **Rationale**: In PHP, a nested static closure cannot bind `$this` from the outer scope. Each arrow function's `static` eligibility is independent. The outer function is still `static`-eligible if only the inner one references `this`.
- **Alternatives considered**: Recursive descent through nested arrow bodies — rejected; would produce false negatives (outer incorrectly kept non-static) and is semantically wrong.

### 4. `static` placement in PHP

- **Decision**: Emit `static function` (space before `function`).
- **Rationale**: Current code builds `$signature = ' function'` (note leading space, as it is concatenated after an assignment target like `$name =`). The new value is `' static function'`.
- **PHP spec**: `static` must precede the `function` keyword for static closures (`static function() {}`). Confirmed against PHP 8.x documentation.

### 5. Existing case_68 snapshot must be updated

- **Decision**: Update `ArrowFunctionFloat.psc` from `function` to `static function`.
- **Rationale**: case_68's `ArrowFunctionFloat.ps` declares `$calcTotal` with no `this` reference. After this feature lands, the emitter will produce `static function`. The snapshot is the regression baseline; it must reflect the new correct output.
- **Impact**: `ArrowFunctionFloatTest.php` must also be checked for hardcoded strings.

### 6. case_53 (Mapper with `this`) must NOT change

- **Decision**: case_53's snapshot remains unchanged — no `static` prefix.
- **Rationale**: `Mapper.ps` contains `return this.prefix` inside the arrow function body. The new detection correctly identifies `ThisExpressionNode` and emits plain `function`.
- **Verification**: case_53 must pass unchanged after the feature is applied.

### 7. No imports currently present for `ThisExpressionNode` in `ArrowFunctionEmitter`

- **Decision**: Add `use PHireScript\Compiler\Parser\Ast\Nodes\Expressions\ThisExpressionNode;` to `ArrowFunctionEmitter.php`.
- **Rationale**: The class is not currently imported in that file. The `ArrowFunctionNode` import is already present (line 10 of the file).
