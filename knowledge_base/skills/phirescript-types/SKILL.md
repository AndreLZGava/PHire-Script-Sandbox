---
name: phirescript-types
description: PHireScript type system — primitives, super types, union types, collections, and how they map to PHP
metadata:
  type: skill
---

# Skill: PHireScript Types

## Triggers

- "what types does PHireScript support", "how do I declare a union type"
- "what is a super type", "Email type", "Uuid type"
- "what's the PHP equivalent of PHireScript type X"
- "how do I type a method return", "null handling in PHireScript"

## When to Use

Use when writing `.phs` files and you need to know what types are valid and how they compile to PHP.

## Repository Context

- Type examples: `samples/success/case_28/UserCredentials.phs`, `samples/success/case_28/AuthenticatorClass.phs`
- Interface examples: `samples/success/case_7/UserInterface.phs`
- Type-focused cases: tags `types`, `type`, `super-types`, `super-type`, `primitives`

## Key Patterns

### Primitive Types

| PHireScript | PHP equivalent | Notes                          |
|-------------|----------------|--------------------------------|
| `String`    | `string`       | Can use method chaining        |
| `Int`       | `int`          | Can use method chaining        |
| `Float`     | `float`        | Can use method chaining        |
| `Bool`      | `bool`         | Can use method chaining        |
| `Null`      | `null`         | Null literal                   |
| `Void`      | `void`         | Method return only             |
| `Mixed`     | `mixed`        | Any type                       |
| `Any`       | `mixed`        | Alias for Mixed                |
| `Array`     | `array`        | Array type                     |
| `Object`    | `object`       | PHP object / stdClass          |

### Super Types (validated string types)

Super types compile to PHP classes with built-in validation. They are strings under the hood but enforce a format constraint.

| PHireScript | Validates as           |
|-------------|------------------------|
| `Email`     | Email address (regex)  |
| `Uuid`      | UUID v4                |
| `Ipv4`      | IPv4 address           |
| `Ipv6`      | IPv6 address           |
| `Color`     | Hex color code         |
| `Url`       | HTTP/HTTPS URL         |
| `Cron`      | Cron expression        |
| `Duration`  | ISO 8601 duration      |
| `Date`      | Date/DateTime          |
| `Slug`      | URL-safe slug          |

Usage in `.phs`:

```
type UserCredentials as scoped {
    Email userEmail
    + Date dateBirth
    # Ipv4|Ipv6 lastIp
}
```

### Union Types

```
String|Null
Null|UserCredentials
Ipv4|Ipv6
String|Int|Bool
```

Syntax: type names joined by `|` without spaces.

### Collections (future / partial support)

```
Array           → array
Object          → object
List<String>    → typed list (partial support)
Map<String>     → typed map (partial support)
```

### Visibility Modifiers in `type` declarations

```
type Foo as scoped {
    String publicField        // default: public
    + Date explicitPublic     // + = public
    # Ipv4 privateField       // # = private
    ~ String protectedField   // ~ = protected
}
```

### Using types as method parameters

```
authenticate(Null|UserCredentials credentials = null): Bool {
    return true
}

login(Email email, String password): Bool {
    return true
}
```

### Return type annotation

```
method(): String|Null { return null }
method(): Bool { return true }
method(): Void { return }
method(): Null { return null }    // always returns null
```

### Type inference (variables)

PHireScript infers types at assignment — no explicit annotation required:

```
price = 19.90        // inferred Float
name = 'hello'       // inferred String
flag = true          // inferred Bool
arr = []             // inferred Array
```

## Critical Rules

1. **All type names are Capitalized** — `String` not `string`, `Null` not `null`, `Bool` not `bool`.
2. **`Void` is only valid as a method return type** — not for variables or parameters.
3. **`Null` as parameter type** means the parameter is nullable — use union: `Null|String`.
4. **Super types are not PHP primitives** — `Email` becomes a class, not just `string`.
5. **Union type syntax has no spaces around `|`** — `String|Null` not `String | Null`.

## Common Mistakes

- Using lowercase `string`, `bool`, `int` → syntax error or wrong compilation
- Returning `null` (lowercase) in a `Null`-typed method → works, but `Null` is a type, `null` is a literal
- Confusing `Void` (no value returned) with `Null` (returns null explicitly)
- Using `Array<String>` — generic array syntax is partially supported; prefer `Array` for safety

## Validation Checklist

- [ ] All type names start with uppercase
- [ ] Union types use `|` without surrounding spaces
- [ ] `Void` methods have `return` with no value
- [ ] `Null` methods have `return null`
- [ ] Super types used only where the compiler supports them (Email, Uuid, etc.)
- [ ] Visibility modifiers in `type` bodies are `+`, `#`, or `~` (not `public`/`private`/`protected`)

## Examples

See: [examples/](examples/)
