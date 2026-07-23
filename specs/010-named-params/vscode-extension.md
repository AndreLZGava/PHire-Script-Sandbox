# VS Code Extension: Named Parameters Support (010)

**Date**: 2026-07-09
**Feature**: Named Parameters in Method Calls
**Branch**: `010-named-params`

This document describes what the PHireScript VS Code extension (`phpscript-vscode/`) needs to do to support named parameter syntax. No implementation tasks are generated here — this is documentation only.

---

## 1. Syntax Highlighting

### What to highlight

Named argument syntax `paramName: value` inside method call parentheses needs distinct highlighting:

- The **parameter name** (`paramName`) should be highlighted as a parameter/label token (not as a variable or identifier).
- The **colon** (`:`) separator should be highlighted as an operator or punctuation.
- The **value** that follows is already highlighted by existing value rules.

### TextMate grammar change

In the grammar file (likely `syntaxes/phirescript.tmLanguage.json` or `.tmlanguage`), add a pattern scoped to the inside of function call argument lists:

```json
{
  "name": "meta.named-arg.phirescript",
  "match": "\\b([a-z][a-zA-Z0-9]*)\\s*(:)\\s*",
  "captures": {
    "1": { "name": "variable.parameter.phirescript" },
    "2": { "name": "punctuation.separator.named-arg.phirescript" }
  }
}
```

This pattern should only apply inside parentheses that follow a dot-chained method call (i.e., after `.methodName(`). Scope it inside the existing method-call parentheses rule.

---

## 2. Autocomplete / IntelliSense

When a developer types inside method call parentheses and the method is known:

- If the call site already has at least one `identifier:` (named arg style), autocomplete should suggest the **remaining parameter names** of that method, followed by `: `.
- If the call site is currently positional (no `identifier:` present), autocomplete should offer both the value completions (existing) and a new set of completions that insert `paramName: ` for each parameter.

### Completion item format

```
separator: |         (triggers value completion after `:`)
enclosure: |
escape: |
```

Each completion item:
- Label: `paramName:`
- Kind: `Parameter`
- InsertText: `${name}: $0` (snippet with cursor after colon)
- Detail: the parameter type from the `BaseParams` definition
- Documentation: indicate whether the parameter is required or optional (and if optional, its default value)

---

## 3. Diagnostics (inline errors)

The extension should surface named-arg errors inline without a full compile:

| Error condition | Diagnostic message | Severity |
|---|---|---|
| Mixed positional and named args | "Cannot mix positional and named arguments in the same call" | Error |
| Unknown parameter name | "Unknown named argument: '{name}'" | Error |
| Missing required parameter | "Missing required named argument: '{name}'" | Error |
| Duplicate parameter name | "Duplicate named argument: '{name}'" | Error |

These diagnostics should appear as red underlines on the offending token(s) and show in the Problems panel.

---

## 4. Hover Information

When hovering over a named argument identifier (`separator` in `getCsv(separator: ',')`):

- Show the `BaseParams` metadata: type, required/optional, default value.
- Example hover:
  ```
  separator: string
  Optional. Default: ','
  ```

---

## 5. Snippets

Add a snippet for named-argument method calls. Example for `getCsv`:

```json
{
  "getCsv (named)": {
    "prefix": "getCsvNamed",
    "body": ".getCsv(separator: '${1:,}', enclosure: '${2:\"}')",
    "description": "getCsv with named parameters"
  }
}
```

A generic snippet for any method with named args:

```json
{
  "Named method call": {
    "prefix": "namedcall",
    "body": ".${1:method}(${2:param}: ${3:value})",
    "description": "Method call with named argument"
  }
}
```

---

## 6. Out of Scope

- Renaming/refactoring parameter names across files (future tooling).
- Auto-converting positional calls to named calls (quick-fix, future).
- Validation against the actual `BaseParams` definitions at design time — requires LSP integration (TI-13, not yet implemented).
