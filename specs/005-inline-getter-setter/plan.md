# Implementation Plan: Inline Getter/Setter Declaration

**Branch**: `005-inline-getter-setter` | **Date**: 2026-06-06 | **Spec**: [spec.md](spec.md)

## Summary

Allow declaring getters and setters directly on property lines inside `class`, `type`, `immutable`, and `trait` bodies using `<` (getter) and `>` (setter) markers with optional visibility prefixes. The compiler generates the corresponding PHP methods at emission time; explicit methods with matching names override generation. The scanner already tokenizes combined forms (`#<`, `+>`, `<>`, etc.) as `T_ACCESSORS` — this feature wires the existing token infrastructure into the parser and emitter.

## Technical Context

**Language/Version**: PHP 8.2 (compiler source), PHireScript (language being compiled)

**Primary Dependencies**: `phirescript/` compiler pipeline — Scanner, Parser, Emitter. No new external dependencies.

**Storage**: N/A — compiler operates in memory; outputs `.php` files.

**Testing**: PHPUnit (sandbox `CaseValidation.php`), `composer quality` inside `phirescript/`.

**Target Platform**: Linux/macOS, CLI PHP 8.2+.

**Project Type**: Compiler/transpiler — changes affect the parsing and emission pipeline.

**Performance Goals**: No measurable regression on existing 54 sandbox cases.

**Constraints**: Token advance rule — only `Parser.php:64` calls `$tokenManager->advance()`. Resolvers/Contexts use read-only token methods only.

**Scale/Scope**: Changes touch ModifiersResolver, PropertyResolver, PropertyNode, ClassBodyEmitter (and equivalents for trait/type/immutable). Blast radius: medium.

## Constitution Check

Constitution file contains only a placeholder template — no project-specific gates defined. The PHireScript-specific architectural gates from `CLAUDE.md` apply instead:

- [x] **Token advance rule** — no Resolver or Context will call `advance()` directly
- [x] **Trinity completeness** — no new Context/Node/Resolver trinity needed (extending existing ones)
- [x] **Binder ordering** — no new Binders introduced
- [x] **Emitter registration** — new `GetterSetterEmitter` must be registered in `src/Emitter.php`
- [x] **Critical area blast radius** — Scanner untouched; PropertyResolver extended (not rewritten); ClassBodyEmitter extended

## Project Structure

### Documentation (this feature)

```text
specs/005-inline-getter-setter/
├── plan.md              ← this file
├── research.md          ← phase 0 output
├── data-model.md        ← phase 1 output
└── tasks.md             ← phase 2 output (/speckit-tasks)
```

### Source Code — files touched

```text
phirescript/src/
├── Compiler/
│   ├── Parser/
│   │   ├── Ast/
│   │   │   ├── Nodes/OOP/
│   │   │   │   └── PropertyNode.php               ← add getter/setter fields
│   │   │   └── Resolver/
│   │   │       └── Root/
│   │   │           └── ModifiersResolver.php       ← add < > T_ACCESSORS recognition
│   │   │       └── Declaration/
│   │   │           └── PropertyResolver.php        ← parse getter/setter from accumulated modifiers
│   ├── Emitter/
│   │   ├── Emitter.php                             ← register GetterSetterEmitter
│   │   └── OOP/
│   │       └── GetterSetterEmitter.php             ← NEW: emit generated getter/setter methods
│   │       └── ClassBodyEmitter.php                ← call GetterSetterEmitter after children
samples/
└── success/
    ├── case_55/                                    ← getter only
    ├── case_56/                                    ← setter only
    ├── case_57/                                    ← getter + setter combined
    ├── case_58/                                    ← visibility variants
    └── case_59/                                    ← explicit override suppresses generated
```

## Implementation Architecture

### How tokenization works today

The Scanner (line 58) already produces `T_ACCESSORS` tokens for combined forms:

| Token value | Meaning |
|-------------|---------|
| `<` (T_SYMBOL) | public getter marker |
| `>` (T_SYMBOL) | public setter marker |
| `*<` (T_ACCESSORS) | public getter (explicit) |
| `#<` (T_ACCESSORS) | private getter |
| `+<` (T_ACCESSORS) | protected getter |
| `*>` (T_ACCESSORS) | public setter (explicit) |
| `#>` (T_ACCESSORS) | private setter |
| `+>` (T_ACCESSORS) | protected setter |
| `<>` (T_ACCESSORS) | public getter + public setter |
| `><` (T_ACCESSORS) | public setter + public getter |

The spaced form `# < + > Bool isAdmin` produces individual T_SYMBOL tokens: `#`, `<`, `+`, `>`, then `Bool`.

### Token accumulation flow (spaced form example)

For `# < + > Bool isAdmin` inside a ClassBodyContext:

1. Token `#` → `ModifiersResolver.isTheCase()` fires (already in MODIFIERS list) → accumulated: `['#']`
2. Token `<` → **after this change**: `ModifiersResolver.isTheCase()` fires → accumulated: `['#', '<']`
3. Token `+` → `ModifiersResolver.isTheCase()` fires → accumulated: `['#', '<', '+']`
4. Token `>` → **after this change**: `ModifiersResolver.isTheCase()` fires → accumulated: `['#', '<', '+', '>']`
5. Token `Bool` → `PropertyResolver.isTheCase()` fires (type + identifier + EOL pattern) → reads `consumePrevious()` = `['#', '<', '+', '>']`

### Accumulated modifiers parsing algorithm

Given array like `['#', '<', '+', '>', '#']` for `+ < # > # Array metadata`:

```
getterVis = null, setterVis = null, pendingVis = null

for each token in accumulated:
  if token is accessor-only (`<` or contains `<` without `>`):
    getterVis = pendingVis ?? 'public'
    pendingVis = null
  else if token is accessor-only (`>` or contains `>` without `<`):
    setterVis = pendingVis ?? 'public'
    pendingVis = null
  else if token is both (`<>` or `><`):
    getterVis = pendingVis ?? 'public'
    setterVis = 'public'
    pendingVis = null
  else (visibility modifier `#`, `+`, `*`):
    pendingVis = map(token)  // '#'→'private', '+'→'protected', '*'→'public'

propertyVis = pendingVis ?? 'public'   // whatever is left after last accessor
```

For `['#', '<', '+', '>']` from `# < + > Bool isAdmin`:
- `#` → pendingVis = 'private'
- `<` → getterVis = 'private', pendingVis = null
- `+` → pendingVis = 'protected'
- `>` → setterVis = 'protected', pendingVis = null
- propertyVis = null → 'public'

Result: property=public, getter=private, setter=protected ✓

For T_ACCESSORS combined token `#<`:
- Single token `#<` → getterVis = 'private' (modifier encoded in token), pendingVis = null
- No pending → propertyVis = 'public'

### Override detection

At emission time, `ClassBodyEmitter` already iterates all `children`. Before emitting getters/setters, it collects the names of explicitly declared methods in `children`. For each `PropertyNode` with a getter or setter, check if a `MethodDeclarationNode` with the matching name (`get{PascalName}` / `set{PascalName}`) exists in the same children list. If yes — skip that generated method.

### Getter/setter PHP output

**Getter** for `< Int id` (property visibility public):
```php
public function getId(): int
{
    return $this->id;
}
```

**Setter** for `> String username` (property visibility public):
```php
public function setUsername(string $username): void
{
    $this->username = $username;
}
```
For supertypes (e.g., `Email`), the setter body uses the same cast pattern as `PhpTypeResolver::assignment()` — e.g., `$this->email = Email::cast($email)`.

For nullable types (`String?`), both getter return type and setter parameter use `?string`.

## Complexity Tracking

No constitution violations. No new architectural patterns introduced beyond what already exists in the emitter.
