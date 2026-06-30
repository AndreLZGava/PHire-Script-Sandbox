# Data Model: Inline Getter/Setter Declaration

**Date**: 2026-06-06

## AST Changes

### PropertyNode (extended)

```
PropertyNode
├── token: Token               (unchanged)
├── types: string[]            (unchanged)
├── name: string               (unchanged)
├── value: ?Node               (unchanged)
├── modifiers: string[]        (unchanged — property visibility, e.g. ['public'])
├── resolvedTypeInfo: array[]  (unchanged)
├── getter: ?string            ← NEW — visibility of generated getter, or null
└── setter: ?string            ← NEW — visibility of generated setter, or null
```

**Lifecycle**: `getter` and `setter` are populated by `PropertyResolver.resolve()` when it parses the accumulated modifier list from `consumePrevious()`. They remain `null` when no `<`/`>` marker is present on the property line.

---

## Parsing Algorithm

### Inputs
- Accumulated modifiers array from `consumePrevious()` — e.g., `['#', '<', '+', '>']`
- May contain: visibility symbols (`#`, `+`, `*`), accessor symbols (`<`, `>`), or T_ACCESSORS combined token values (`#<`, `+>`, `<>`, etc.)

### Output
- `propertyVis: string` — visibility for the property itself
- `getterVis: ?string` — visibility for the getter method (null = no getter)
- `setterVis: ?string` — visibility for the setter method (null = no setter)

### Algorithm

```
pendingVis ← null
getterVis  ← null
setterVis  ← null

for each token in accumulated:

  if token ∈ { '<', '*<', '#<', '+<' }:          // getter-only accessor
    v = extract_vis(token) ?? pendingVis ?? 'public'
    getterVis ← v
    pendingVis ← null

  else if token ∈ { '>', '*>', '#>', '+>' }:       // setter-only accessor
    v = extract_vis(token) ?? pendingVis ?? 'public'
    setterVis ← v
    pendingVis ← null

  else if token ∈ { '<>', '><' }:                  // both
    getterVis ← pendingVis ?? 'public'
    setterVis ← 'public'
    pendingVis ← null

  else if token ∈ { '#', '+', '*' }:               // plain visibility
    pendingVis ← map(token)  // '#'→'private', '+'→'protected', '*'→'public'

propertyVis ← pendingVis ?? 'public'    // whatever visibility wasn't consumed
```

`extract_vis(token)` maps the prefix character of a combined T_ACCESSORS token: `#` → `'private'`, `+` → `'protected'`, `*` → `'public'`, `<`/`>` alone → null (defers to pendingVis).

### Examples

| PHireScript line | accumulated | property | getter | setter |
|---|---|---|---|---|
| `< Int id` | `['<']` | public | public | null |
| `* > Email email` | `['*', '>']` | public | null | public |
| `< > String username` | `['<', '>']` | public | public | public |
| `# < + > Bool isAdmin` | `['#', '<', '+', '>']` | public | private | protected |
| `+ < # > # Array metadata` | `['+', '<', '#', '>', '#']` | private | protected | private |
| `#< String x` (T_ACCESSORS) | `['#<']` | public | private | null |
| `<> String y` (T_ACCESSORS) | `['<>']` | public | public | public |

---

## Emission Model

### GetterSetterEmitter inputs

Receives: `ClassBodyNode` + `EmitContext`

Steps:
1. Collect explicit method names: scan `ClassBodyNode.children` for `MethodDeclarationNode` instances, build `Set<string>` of their `.name` values (normalized: `getId`, `setEmail`, etc.)
2. For each `PropertyNode` in children:
   - If `getter` is not null AND `get{PascalName}` ∉ explicit names → emit getter
   - If `setter` is not null AND `set{PascalName}` ∉ explicit names → emit setter

### Getter template

```php
{visibility} function get{PascalName}(): {phpType}
{
    return $this->{name};
}
```

`{phpType}` = `PhpTypeResolver::phpType($prop)` + `?` prefix if nullable.

### Setter template

```php
{visibility} function set{PascalName}({phpType} ${name}): void
{
    {assignment}
}
```

`{assignment}` = `PhpTypeResolver::assignment($prop, $uses)` — handles primitives, supertypes, metatypes consistently.

### PascalCase conversion

`id` → `Id`, `isAdmin` → `IsAdmin`, `metadata` → `Metadata`

Rule: capitalize first character. Camel-case property names produce correct PascalCase method name suffixes when prefixed with `get`/`set`.

---

## Contexts that need updating

| Context | Change |
|---|---|
| `ModifiersResolver` | Add `'<'`, `'>'` to `MODIFIERS` constant; add `T_ACCESSORS` token recognition in `isTheCase()` |
| `PropertyResolver` | Parse getter/setter from accumulated modifiers; populate `PropertyNode.getter` / `.setter` |
| `ClassBodyEmitter` | After emitting children, call `GetterSetterEmitter` |
| `TraitEmitter` | Same — after emitting properties, call `GetterSetterEmitter` |
| `Emitter.php` | Register `GetterSetterEmitter` |

---

## Non-changes (explicitly out of scope)

- `Scanner.php` — no changes; `T_ACCESSORS` regex already covers combined forms
- `InterfaceBodyContext` — not affected; interface bodies parse method signatures, not properties
- `ValidateBodyContext` — not affected; test construct
- `Binder` and `Checker` — no new binding or semantic checking required for this feature
