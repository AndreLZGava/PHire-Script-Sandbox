# Implementation Plan: DotResolver Fix — Chain Emit in Assignment/Return Contexts

**Branch**: `007-fix-dot-resolver` | **Date**: 2026-06-30 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `specs/007-fix-dot-resolver/spec.md`

---

## Summary

Single-call chains (`result = this.label.toUpperCase()`) already compile correctly. Multi-call chains (`result = this.label.toUpperCase().removeSpaces()`) parse correctly but **emit broken PHP** when the inner method's `phpCodeForConversion` is an array (multi-line IIFE). `FunctionEmitter::wrapAsIIFE()` passes the raw expression string as the `use (...)` capture variable, which is invalid PHP syntax. The fix is: when wrapping in an IIFE and the computed `$self` expression is not a simple PHP variable, materialise it into a temporary `$__chain_N` variable first.

---

## Diagnosis

### What already works

- `result = this.label.toUpperCase()` → `$result = \mb_strtoupper($this->label, 'UTF-8');` ✅
- `return this.label.toUpperCase()` → `return \mb_strtoupper($this->label, 'UTF-8');` ✅
- `result = someString.add('x')` in `ProgramContext` ✅

### What breaks

- `result = this.label.toUpperCase().removeSpaces()` → emits:

  ```php
  $result = (function() use (\mb_strtoupper($this->label, 'UTF-8')) {
      return \trim(\mb_strtoupper($this->label, 'UTF-8'));
  })();
  ```

  `use (expression)` is illegal PHP — `use` only accepts variables.

### Root cause

`FunctionEmitter::wrapAsIIFE(array $lines, string $variable)` uses `$variable` both as the `@self` replacement and as the `use (...)` capture. When `$variable` is a simple PHP variable like `$this->label` this is tolerated (though still wrong syntax — `use ($this->label)` is valid). When `$variable` is a compound expression like `\mb_strtoupper($this->label, 'UTF-8')`, PHP rejects it.

### Correct approach

When `phpCodeForConversion` is an array and `$self` is not a bare PHP variable (`$name` form), materialise it into a temp var:

```php
// Before IIFE:
$__chain_0 = \mb_strtoupper($this->label, 'UTF-8');
// IIFE uses the temp var:
(function() use ($__chain_0) {
    return \trim($__chain_0);
})()
```

A simpler alternative (preferred): **avoid the IIFE entirely** for the single-statement case. If the array has exactly one `return` statement, inline it directly:

```php
// array: ['return @characters !== null ? \trim(@self, @characters) : \trim(@self);']
// → single return → extract expression → inline:
\trim(\mb_strtoupper($this->label, 'UTF-8'))
```

This produces clean PHP without closures and avoids the `use` problem completely.

---

## Technical Context

**Language/Version**: PHP 8.2 (compiler source); PHireScript `.phs` (input language)

**Primary Dependencies**: PHireScript compiler internals — `FunctionEmitter`, `FunctionNode`, `AbstractContext`, `ContextManager`, `DotResolver`

**Storage**: N/A

**Testing**: PHPUnit via `php bin/stretch --mode=success`; manual `php phirescript/bin/build` on sandbox cases

**Target Platform**: PHireScript compiler (`phirescript/`) running on Linux/PHP 8.2

**Project Type**: Compiler internal — emitter layer fix

**Performance Goals**: No measurable impact; emit is not on the hot path

**Constraints**: Token advance rule — only `Parser.php` may call `$tokenManager->advance()`. This fix is in the Emitter, so the rule is not applicable here.

**Scale/Scope**: 3 files changed; 1 new sandbox case

---

## Constitution Check

The constitution template is not yet filled for this project. No blocking gates apply. The PHireScript CLAUDE.md architectural constraint that applies here:

- **Token advance rule**: Not violated — this fix is entirely in the Emitter layer, no Parser or Resolver changes needed.
- **Trinity completeness** (Parser + Binder + Emitter per feature): Not applicable — this is a bug fix in an existing Emitter, not a new language construct.

---

## Project Structure

### Documentation (this feature)

```text
specs/007-fix-dot-resolver/
├── plan.md              ← this file
├── research.md          ← Phase 0 output
├── checklists/
│   └── requirements.md
└── tasks.md             ← Phase 2 output (/speckit-tasks)
```

### Source Code (files to change)

```text
phirescript/src/
└── Compiler/
    └── Emitter/
        └── Declarations/
            └── FunctionEmitter.php        ← primary fix: wrapAsIIFE + emitChain helper

samples/success/
└── case_67/
    ├── ChainAssignment.phs                 ← new sandbox case
    └── CaseValidation.php
```

---

## Phase 0: Research

### Decision: Inline extraction vs temp variable materialisation

**Chosen approach**: Inline extraction for single-`return` array methods.

When `phpCodeForConversion` is an array with a single element that starts with `return `, extract the expression after `return ` and inline it directly as the `@self` substitution for the outer call. This avoids closures entirely.

For multi-statement arrays (currently none in the runtime), fall back to temp-variable materialisation (`$__chain_N`).

**Rationale**: Inline extraction produces cleaner, more readable PHP. All current multi-line `phpCodeForConversion` arrays in the runtime are single `return` statements (`removeSpaces`, etc.). The closure approach was designed for side-effect methods (`show!`, `destroy!`) — not for expression methods used in chains.

**Alternatives considered**:
- Full IIFE with temp var: works but adds closure overhead and visual noise for simple chains.
- Static counter for temp var names: fragile across nested chains; inline is simpler.

### Decision: Parser change needed?

**No**. The parser already handles multi-call chains correctly — `FunctionCallResolver` and `DotResolver` work. The bug is purely in emit. No `ContextManager`, `AbstractContext`, or `onClosingToken()` changes are needed for this fix. The `onClosingToken()` refactor (TD-18) remains a separate future item.

### Decision: Which contexts need sandbox validation?

- `AssignmentContext`: `result = this.label.toUpperCase().removeSpaces()`
- `ReturnContext`: `return this.label.toUpperCase().removeSpaces()` (verify no regression)
- `IfConditionContext`: out of scope for this fix — chains in if-conditions require a separate investigation

---

## Deferred

### IfConditionContext chain support (FR-003)

`IfConditionContext` chain support is deferred to a future spec. Per research in Phase 0, chains in if-conditions (`if (this.label.empty?())`) require a separate investigation: the condition context has different closing-token semantics and the focus-propagation path is not handled by the Emitter alone. This is a parser/context-level concern outside the scope of this Emitter-only fix.

---

## Phase 1: Design

### FunctionEmitter change

**File**: `phirescript/src/Compiler/Emitter/Declarations/FunctionEmitter.php`

**Method**: `wrapAsIIFE` → replaced or guarded by `emitChainedExpression`

```php
private function overrideSelf($node, $ctx): string
{
    $variable = $ctx->emitter->emit($node->variableBase, $ctx);
    $method = $node->method->phpCodeForConversion;

    if (\is_array($method)) {
        return $this->emitChainedExpression($method, $variable, $node, $ctx);
    }

    return \str_replace('@self', $variable, $method);
}

private function emitChainedExpression(array $lines, string $self, $node, $ctx): string
{
    // Single return statement → extract and inline
    if (\count($lines) === 1 && \str_starts_with(\ltrim($lines[0]), 'return ')) {
        $expr = \preg_replace('/^\s*return\s+/', '', $lines[0]);
        $expr = \rtrim($expr, '; ');
        return \str_replace('@self', $self, $expr);
    }

    // Multi-statement → materialise $self into a temp var
    static $counter = 0;
    $tmpVar = '$__chain_' . $counter++;
    $indented = \implode("\n    ", \array_map(
        fn($l) => \str_replace('@self', $tmpVar, $l),
        $lines
    ));
    return "({$tmpVar} = {$self}) !== null ? (function() use ({$tmpVar}) {\n    {$indented}\n})() : null";
}
```

**Key rule**: `wrapAsIIFE` stays for the `SafeNavigation` path (already correct there — uses its own temp var). Only `overrideSelf` is changed.

### Sandbox case_67

**File**: `samples/success/case_67/ChainAssignment.phs`

```
pkg PHireScript.Samples67

class ChainAssignment as scoped {
    String label

    processAssignment(): String {
        result = this.label.toUpperCase().removeSpaces()
        return result
    }

    processReturn(): String {
        return this.label.toUpperCase().removeSpaces()
    }
}
```

**Expected PHP output**:
```php
public function processAssignment(): string
{
    $result = \trim(\mb_strtoupper($this->label, 'UTF-8'));
    return $result;
}

public function processReturn(): string
{
    return \trim(\mb_strtoupper($this->label, 'UTF-8'));
}
```

**CaseValidation**: asserts `✔ src/output/ChainAssignment.phs → src/compiled/ChainAssignment.php` and verifies output via PHPUnit.
