# VS Code Extension Support: User-Defined Method Calls as Expression Operands

**Feature**: `011-fix-dot-resolver` | **Date**: 2026-07-09

---

## Overview

This feature does not introduce new syntax to PHireScript. The expression `this.methodName()` is already valid syntax that the extension highlights correctly. The change is purely in the compiler's ability to resolve user-defined method calls in expression operand position.

---

## Impact on the Extension

### Syntax Highlighting

No changes required. The existing patterns for:
- `this` keyword highlighting
- `.` dot operator
- Method call `identifier()`

already cover `this.getBase() * this.getRate()` correctly.

### Diagnostics

Currently, the extension may show no error for `this.getBase() * this.getRate()` since it does not perform semantic analysis (that's the compiler's job). After this feature, the compiler will also not error — the diagnostic gap is closed by the fix itself.

**No extension change needed.**

### Autocomplete

**Potential improvement** (not required for this feature, but worth noting for future work):

After typing `this.`, the extension could suggest methods declared in the current class. This would require the extension's language server to parse the current file and extract method signatures — a non-trivial change that belongs in a dedicated extension feature.

**Out of scope for this feature.**

### Snippets

No new snippets needed. The existing method declaration snippet (`# methodName(): ReturnType { }`) already covers the pattern.

---

## Summary

No extension changes are required for this feature. The fix is entirely compiler-side.
