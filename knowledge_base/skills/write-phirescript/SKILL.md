---
name: write-phirescript
description: Write syntactically correct PHireScript (.phs) source files including classes, interfaces, traits, types, imports, and control flow
metadata:
  type: skill
---

# Skill: Write PHireScript

## Triggers

- "write a .phs file", "create PHireScript source", "add a class in PHireScript"
- "how do I declare a trait / interface / type in PHireScript"
- "what's the syntax for imports / methods / conditionals"

## When to Use

Use when creating or editing `.phs` source files in any case directory.

## Repository Context

- Source files: `samples/success/case_N/*.phs`
- Real examples: `samples/success/case_28/AuthenticatorClass.phs`, `samples/success/case_3/MagicMethods.phs`
- Compiled output: `src/compiled/*.php`
- Compiler: `phirescript/bin/build`

## Key Patterns

### Package declaration (required, first line)

```
pkg PHireScript.SamplesN        // N = case folder number
```

### Imports

```
use PHireScript.SamplesN.ClassName
use PHireScript.SamplesN.{
    ClassA
    ClassB as AliasB,
    ClassC
}
external Symfony\Component\DependencyInjection\Loader\GlobFileLoader
```

### Class

```
class ClassName as singleton {
    methodName(ParamType paramName): ReturnType {
        return value
    }
}
```

Scope modifiers: `newable`, `singleton`, `scoped` (or omit for default).

### Class with trait and interfaces

```
class ClassName with TraitName as singleton implements InterfaceA, InterfaceB {
    ...
}
```

### Abstract class

```
abstract class Repository {
    abstract String tableName      // abstract property

    methodExample(): Null {
        return null
    }
}
```

### Interface

```
interface Authenticator {
    save?(Array data): Bool
    delete!(): Void
    getCompleteUserName(): String|Null
}
```

Method markers: `?` = optional parameter, `!` = required parameter.

### Trait

```
trait Logger {
    log(String msg): String {
        return "message"
    }
}
```

### Type (DTO)

```
type UserCredentials as scoped {
    String login
    Email userEmail
    + Date dateBirth       // + = public
    # Ipv4|Ipv6 lastIp    // # = private
}
```

### Methods — return types

```
method(): Bool { return true }
method(): Void { return }
method(): Null { return null }
method(): String { return 'text' }
method(): Float { return 15.2 }
method(): Int { return 10 }
method(): Array { return [] }
method(): Object { return {} }
method(): String|Null { return null }
```

### Method parameters

```
method(String name): Void { ... }
method(String name, Bool flag = true): Void { ... }
method(Null|UserCredentials credentials = null): Bool { return true }
```

### Magic methods

| PHireScript      | PHP equivalent   |
|------------------|------------------|
| `onCreate`       | `__construct`    |
| `onDestroy`      | `__destruct`     |
| `onGet`          | `__get`          |
| `onSet`          | `__set`          |
| `hasHas`         | `__isset`        |
| `onUnset`        | `__unset`        |
| `onCall`         | `__call`         |
| `onStaticCall`   | `__callStatic`   |
| `toString`       | `__toString`     |
| `toSerialize`    | `toArray`        |
| `beforeSerialize`| `__sleep`        |
| `afterUnserialize`| `__wakeup`      |
| `onClone`        | `__clone`        |
| `toInspect`      | `__debugInfo`    |

### Variables and literals

```
user = {}                    // empty object
price = 19.90                // float
flag = true                  // bool
name = 'single quotes'
name = "double quotes"
arr = []                     // empty array
arr = ['key': ['a', 'b']]   // array with nested
obj = { 'test': 1 }         // object with fields
```

### Conditionals

```
if(score >= 90) {
    grade = 'A'
} elseif(score >= 80) {
    grade = 'B'
} else {
    grade = 'F'
}
```

### Comments

```
// single line comment
/**
 * multiline docblock
 */
```

## Critical Rules

1. **`pkg` declaration is mandatory** and must be the first statement.
2. **Package must be `PHireScript.SamplesN`** with correct case number — generic names break the web environment.
3. **Return types are declared after `:`** on the method signature, not inside the body.
4. **`Void` methods must still have `return`** with no value.
5. **Union types use `|`** without spaces: `String|Null`, `Ipv4|Ipv6`.
6. **`with` (trait) comes before `as` (scope) comes before `implements`** in class declaration.

## Common Mistakes

- Omitting `pkg` declaration → compiler error
- Using `pkg PHireScript.Samples` (no case number) → cross-case collision
- Writing `return;` instead of `return` (no semicolons in PHireScript)
- Using `null` (lowercase) instead of `Null` (capitalized type)
- Using `void` instead of `Void`
- Putting scope after `implements`: ~~`class Foo implements Bar as singleton`~~ → correct: `class Foo as singleton implements Bar`

## Validation Checklist

- [ ] File starts with `pkg PHireScript.SamplesN`
- [ ] Correct case number N matches the folder
- [ ] All imported classes are declared with `use` or `external`
- [ ] Methods have explicit return types
- [ ] `Void` methods end with `return` (no value)
- [ ] No semicolons at end of statements
- [ ] File compiles: `php phirescript/bin/build` (after setting PHireScript.json source)

## Examples

See: [examples/](examples/)
