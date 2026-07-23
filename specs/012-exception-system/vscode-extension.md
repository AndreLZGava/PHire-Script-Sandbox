# VS Code Extension Support: PHireScript Exception System V1

**Date**: 2026-07-22 | **Feature**: [plan.md](plan.md)

This document describes what the `phpscript-vscode` extension must do to support the exception system. No implementation is generated here — this is specification only.

---

## 1. Syntax Highlighting

### New keywords to highlight

Add the following tokens to the grammar as `keyword.control` or `keyword.declaration` as appropriate:

| Token | Category |
|-------|----------|
| `exception` | `keyword.declaration.exception.phirescript` |
| `throws` | `keyword.control.throws.phirescript` |

These must be added to the TextMate grammar (`.tmLanguage.json`) keyword lists.

### Exception declaration pattern

```
exception {Name} [extends {Name}] [{ ... }]
```

- `exception` — keyword
- `{Name}` — `entity.name.type.exception.phirescript`
- `extends` — `keyword.control.extends.phirescript` (already defined for class)
- `{Name}` after `extends` — `entity.name.type.phirescript`

### `throws` in function/method signatures

```
fn!(): ReturnType
throws ExceptionA | ExceptionB
```

- `throws` — keyword
- Exception names after `throws` — `entity.name.type.phirescript`
- `|` separator — `punctuation.separator.union.phirescript`

### `throw` expression (already exists for `try/handle` — verify coverage)

```
throw ExceptionType(
    field: 'value',
    cause: e
)
```

- `throw` — `keyword.control.throw.phirescript` (confirm already covered)
- `ExceptionType` when used in throw-position — `entity.name.type.exception.phirescript`

---

## 2. Snippets

### `exception` — bare declaration

```json
{
  "PHireScript Exception": {
    "prefix": "exception",
    "body": [
      "exception ${1:ExceptionName}"
    ],
    "description": "Declare a PHireScript exception type"
  }
}
```

### `exception` — with properties

```json
{
  "PHireScript Exception with Properties": {
    "prefix": "exceptionp",
    "body": [
      "exception ${1:ExceptionName} {",
      "\t${2:String} ${3:field}",
      "}"
    ],
    "description": "Declare a PHireScript exception with typed properties"
  }
}
```

### `exception` — with message template

```json
{
  "PHireScript Exception with Template": {
    "prefix": "exceptiont",
    "body": [
      "exception ${1:ExceptionName} {",
      "\t${2:String} ${3:field}",
      "",
      "\tmessage: '${4:Error in {field}}'",
      "}"
    ],
    "description": "Declare a PHireScript exception with a message template"
  }
}
```

### `throw` with named args

```json
{
  "PHireScript throw": {
    "prefix": "throw",
    "body": [
      "throw ${1:ExceptionName}(",
      "\t${2:field}: ${3:'value'}",
      ")"
    ],
    "description": "Throw a PHireScript exception"
  }
}
```

### `throws` annotation

```json
{
  "PHireScript throws annotation": {
    "prefix": "throws",
    "body": [
      "throws ${1:ExceptionName}"
    ],
    "description": "Declare checked exceptions on a function or method"
  }
}
```

---

## 3. Autocomplete

### `exception` body members

When inside an `exception { }` body, suggest:
- Typed property declarations (`String fieldName`)
- `message:` template key
- `constructor(` block

### `throw ExceptionName(` named args

When completing a `throw ExceptionType(` expression, suggest the declared property names of the resolved exception type as named argument keys.

### `throws` type list

When the cursor is after `throws ` in a function/method signature, suggest all `exception` types declared in the current package/imports.

---

## 4. Diagnostics

The following compile-time errors produced by the checker passes should be surfaced as VS Code diagnostics (red squiggles) when the extension has compile-on-save enabled:

| Error | Diagnostic message |
|-------|--------------------|
| Unhandled checked exception | `Exception 'X' declared in throws must be handled or propagated` |
| Immutability violation | `Exception property '{name}' is readonly and cannot be reassigned` |
| Exception instantiation outside throw | `Exceptions can only be created via 'throw'; direct instantiation is not allowed` |
| Unknown type in `throws` | `Type '{name}' in throws declaration is not a declared exception` |

---

## 5. Hover Information

When hovering over an exception type name (in a `throw`, `handle`, or `throws` clause), show:
- The exception's declared properties and their types
- The message template (if declared)
- The inheritance chain

---

## 6. Language Grammar Scope Summary

| Construct | Scope |
|-----------|-------|
| `exception` keyword | `keyword.declaration.exception.phirescript` |
| Exception type name in declaration | `entity.name.type.exception.phirescript` |
| `extends` (reuse) | `keyword.control.extends.phirescript` |
| `throws` keyword | `keyword.control.throws.phirescript` |
| Exception type in `throws` | `entity.name.type.phirescript` |
| `message:` template key | `variable.other.member.phirescript` |
| `cause:` / `context:` / `code:` in throw | `variable.other.named-arg.phirescript` |
