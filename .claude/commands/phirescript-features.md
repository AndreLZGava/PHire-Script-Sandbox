# PHireScript — Language Feature Reference

> Use this skill to understand what PHireScript features are currently implemented before proposing new syntax, designing new constructs, or reviewing AI-generated `.phs` code.
> When new features are implemented and validated (passing `php bin/stretch`), update this file.

> Related: [`compiler-update`](./compiler-update.md) — how to implement features in the compiler pipeline.

> **Language inspirations (in order):** TypeScript → Ruby → Java. When a syntax choice is ambiguous, this order is the tiebreaker. Many structural decisions (type inference, union types, method return annotations) come from TypeScript; suffix conventions (`?`, `!`) come from Ruby; class/interface/abstract patterns come from Java.

---

## 1. Package System

### Package declaration
Every `.phs` file that defines a named construct must declare its package:
```
pkg PHireScript.SamplesN
```

### Imports
```
use PHireScript.Samples28.{UserCredentials, Another}
use PHireScript.Samples28.{
    UserCredentials
    User as UserAccess,
    Authenticator,
}
```

### External PHP imports
```
external Symfony\Component\DependencyInjection\Loader\GlobFileLoader
```

---

## 2. Top-Level Constructs

| Construct | Keyword | Description |
|---|---|---|
| Class | `class Name` | Full OOP class |
| Abstract class | `abstract class Name` | Abstract class with abstract members |
| Interface | `interface Name` | Contract definition |
| Type | `type Name as scoped` | Lightweight data shape (like a DTO) |
| Immutable | `immutable Name as scoped` | Read-only data shape |
| Trait | `trait Name` | Reusable behavior mixin |

### Class modifiers

```
class Foo as newable { }          // instantiable (new Foo())
class Foo as singleton { }        // singleton pattern
class Foo as scoped { }           // scoped instance
class Foo with Logger { }         // uses trait Logger
class Foo implements Bar { }      // implements interface Bar
class Foo extends Base { }        // extends class Base

// Combined:
class AuthenticatorClass with Logger as singleton implements Authenticator, Another { }
```

### Abstract class
```
abstract class Repository {
    abstract String tableName

    methodExample(): Null {
        return null
    }
}
```

---

## 3. Interface

```
interface UserInterface {
    save?(Array data): Bool
    delete!(): Void
    getCompleteUserName(): String|Null
}

interface Authenticator extends Another {
    authenticate(UserCredentials credentials): Bool
    logout(): Void
}
```

**Method modifiers (suffix on method name):**
- `?` — indicates the method may return null / is optional (e.g. `save?`)
- `!` — indicates the method is **void**: it executes an action and returns nothing (e.g. `delete!`). Note: this differs from Ruby, where `!` means the method mutates the receiver in-place. In PHireScript `!` is purely about return type (Void).
- `*` prefix in interfaces — indicates the method is optional to implement (e.g. `* save?(Array data): Bool`)

---

## 4. Type and Immutable

```
type UserCredentials as scoped {
    String login
    Email userEmail
    + Date dateBirth       // + = public
    # Ipv4|Ipv6 lastIp    // # = private
    Bool isAdmin = true    // default value
}

immutable UserImmutable as scoped {
    Int id
    String username
    Email email
    Bool isAdmin
    Null|Array metadata
}
```

**Access modifiers inside type/immutable body:**
- `+` — public
- `#` — private
- (no modifier) — protected (default)

---

## 5. Trait

```
trait Logger {
    log(String msg): String {
        return "message"
    }
}
```

---

## 6. Primitive Types

| PHireScript | Maps to PHP |
|---|---|
| `String` | `string` |
| `Int` | `int` |
| `Float` | `float` |
| `Bool` | `bool` |
| `Array` | `array` |
| `Object` | `object` / `stdClass` |
| `Null` | `null` |
| `Mixed` | `mixed` |
| `Void` | `void` |

**Casting syntax:**
```
idAsString = String(12345)      // "12345"
ageFromText = Int("30")         // 30
taxValue = Float("0.15")        // 0.15
statusFromBinary = Bool(1)      // true
singleItemArray = Array(name)   // [$name]
objFromMap = Object(['id': 1])  // stdClass with property id
```

---

## 7. Super Types

SuperTypes are validated wrappers around primitive types:

| SuperType | Validates | Example |
|---|---|---|
| `Email` | Email format | `Email('user@example.com')` |
| `Ipv4` | IPv4 address | `Ipv4('127.0.0.1')` |
| `Ipv6` | IPv6 address | `Ipv6('2001:0db8::1')` |
| `Uuid` | UUID format | `Uuid()` / `Uuid('550e8400-...')` |
| `Color` | Color hex | `Color('#fff')` |
| `Url` | URL format | `Url('www.google.com')` |
| `Cron` | Cron expression | `Cron('@today')` |
| `Duration` | Duration string | `Duration('5m')` |
| `Json` | JSON string | `Json('{"name":"John"}')` |
| `Mac` | MAC address | `Mac('00:1a:2b:3c:4d:5e')` |
| `Slug` | URL slug | `Slug('hello World')` |
| `Date` | Date/time | `Date('2024-01-01')` |

---

## 8. Variables

```
// Type inference (no keyword needed)
userName = "André"
userAge = 25
productPrice = 250.99
isActive = true
techStack = ["PHP", "PS", "TS"]
dataContainer = {id: 1}

// Reference
varReference = anotherVar
```

---

## 9. Arrays

```
// Literal
myArray = ["PHP", "PS", "TS"]

// Associative
variables = [
  true,
  'another',
  'test': ['array'],
  0: 'another test',
  myTest: anotherOne,
]

// Nested
data = [
    0: ['#key': ['#sub': 'value']],
    1: ['#elements': ['#child': 'text']],
]

// Cast
variables2 = Array('test')

// Ranges inside arrays
myTest = [1, 2, 1..10, 50, 2..-10]
```

**Array method chaining:**
```
variables.add('key', 'value')
.add(['test': 0], 'omg')
.destroy!('key')
```

---

## 10. Range

```
1..10     // ascending range: 1,2,3,...,10
2..-10    // descending range: 2,1,0,-1,...,-10
```

---

## 11. Object Literal

```
empty = {}
withProps = {id: 1}
withString = {'test': 1}
```

---

## 12. Control Flow

### if / else / elseif

```
if(condition) {
    // body
}

if(condition) {
    // body
} else {
    // body
}

if(score >= 90) {
    grade = 'A'
} elseif(score >= 80) {
    grade = 'B'
} elseif(score >= 70) {
    grade = 'C'
} else {
    grade = 'F'
}
```

### try / handle / always

```
try {
    variable = 'test'
} handle (Exception e) {
    variable2 = 'another'
} always {
    variable3 = 'always'
}
```

---

## 13. Comparison Operators

| Operator | Meaning |
|---|---|
| `==` | equal |
| `===` | strict equal |
| `!=` | not equal |
| `!==` | strict not equal |
| `>` | greater than |
| `<` | less than |
| `>=` | greater or equal |
| `<=` | less or equal |
| `&&` | logical and |
| `\|\|` | logical or |

```
if(1 > 2) { }
if(1 == 1) { }
if(1 !== 2) { }
if(score >= 90) { }
```

---

## 14. Methods (in class/trait)

```
methodName(Type param, Type param2 = defaultValue): ReturnType {
    return value
}

// Nullable return
getUser(): String|Null {
    return null
}

// Void
logout(): Void {
    return
}

// Optional suffix
save?(Array data): Bool {
    return true
}

// Destructive suffix
delete!(): Void {
    return
}
```

---

## 15. Magic Methods

Mapped PHireScript → PHP magic:

| PHireScript | PHP |
|---|---|
| `onCreate` | `__construct` |
| `onDestroy` | `__destruct` |
| `onGet` | `__get` |
| `onSet` | `__set` |
| `hasHas` | `__isset` |
| `onUnset` | `__unset` |
| `onCall` | `__call` |
| `onStaticCall` | `__callStatic` |
| `toString` | `__toString` |
| `toSerialize` | `__sleep` |
| `beforeSerialize` | `__serialize` |
| `afterUnserialize` | `__unserialize` |
| `onClone` | `__clone` |
| `toInspect` | `__debugInfo` |

---

## 16. Union Types

```
String|Null
Ipv4|Ipv6
String|Int|Bool
```

---

## 17. Constants

PHP constants are referenced directly (no special syntax):
```
test = E_ERROR
```

---

## 18. Comments

```
// inline comment

/**
 * Multi-line comment
 */
```

---

## AI-Friendliness Quick Reference

| Feature | Rating | Note |
|---|---|---|
| `pkg` / `use` / `external` | ★★★★★ | Explicit, unambiguous |
| `type`/`immutable` with `as scoped` | ★★★★★ | Declarative, no logic |
| `if`/`else`/`elseif` | ★★★★★ | Standard pattern |
| `try`/`handle`/`always` | ★★★★★ | Clear semantic replacement for try/catch/finally |
| Union types `A\|B` | ★★★★★ | Standard, predictable |
| Variable inference (no `var`) | ★★★★☆ | Simple rule, AI may add type hints |
| Method return type `: Type` | ★★★★☆ | Clear, consistent |
| SuperTypes as constructors | ★★★★☆ | Pattern is consistent; vocabulary needs learning |
| `abstract class` / `abstract Prop` | ★★★★☆ | Standard keyword |
| `trait` | ★★★★☆ | Standard concept |
| Magic method names | ★★★☆☆ | Need mapping table; non-obvious |
| `+`/`#` access modifiers | ★★★☆☆ | Short but non-standard; easy to forget |
| `?`/`!` method suffix | ★★★☆☆ | Creative but ambiguous: `?` = nullable OR optional? |
| `class X with T as S implements I` | ★★★☆☆ | Word order is uncommon and needs memorizing |
| `*` prefix in interfaces | ★★☆☆☆ | Meaning unclear without context |
| Range `1..10` | ★★★★☆ | Common in many languages |
