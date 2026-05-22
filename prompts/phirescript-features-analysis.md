# PHireScript Feature Analysis — AI Generation Perspective

> This document analyzes each implemented PHireScript feature and ranks it from the perspective of how easy it is for an AI model to generate it correctly without prior training on the language.
>
> **Evaluation criteria:**
> - **Unambiguity**: Does the token/syntax have exactly one meaning regardless of context?
> - **Consistency**: Does the feature follow the same pattern as other features?
> - **Predictability**: Can an AI derive the correct syntax from intent alone?
> - **Learnability**: How many rules/exceptions does an AI need to memorize?

---

## Rating Scale

| Rating | Meaning |
|---|---|
| ★★★★★ | An AI generates this correctly on the first attempt without any examples |
| ★★★★☆ | Mostly predictable; one or two non-obvious rules |
| ★★★☆☆ | Requires training examples; some ambiguity or non-standard convention |
| ★★☆☆☆ | High risk of AI making incorrect assumptions |
| ★☆☆☆☆ | Too ambiguous or unique; AI needs explicit instruction every time |

---

## Feature Rankings

### ★★★★★ Package System (`pkg`, `use`, `external`)

```
pkg PHireScript.Samples28

use PHireScript.Samples28.{UserCredentials, Another}
external Symfony\Component\...
```

**Why it scores high:**
- `pkg` is unambiguous and always at the top of the file
- `use` with `{}` for multiple imports follows a pattern common in Rust, Kotlin, TypeScript
- `external` for PHP-native imports is a clear semantic name
- No edge cases, no modifiers, no ambiguity

---

### ★★★★★ Type / Immutable declaration

```
type UserCredentials as scoped {
    String login
    Email userEmail
    Bool isAdmin = true
}

immutable UserImmutable as scoped {
    Int id
    String username
}
```

**Why it scores high:**
- `type` and `immutable` are clear keywords with distinct semantic meaning
- `as scoped` is always present in the same position
- Body is purely declarative: `Type propertyName` (no logic, no methods)
- Default values follow a standard `= value` pattern
- An AI producing data shapes will naturally produce valid PHireScript types

**Minor note:** The difference between `type` and `immutable` requires a rule (one is mutable, one is not). One additional concept to teach.

---

### ★★★★★ if / else / elseif

```
if(condition) {
    // body
} elseif(condition) {
    // body
} else {
    // body
}
```

**Why it scores high:**
- Identical structure to most C-like languages
- `elseif` (one word, no space) is the only minor variation from PHP's `elseif` — it's the same
- Any AI trained on PHP, Java, C, or JS will produce this correctly

---

### ★★★★★ try / handle / always

```
try {
    // body
} handle (Exception e) {
    // body
} always {
    // body
}
```

**Why it scores high:**
- `handle` is a clear semantic replacement for `catch` — arguably clearer
- `always` is a clear semantic replacement for `finally`
- The structure is identical to the `try`/`catch`/`finally` pattern
- An AI only needs to substitute `catch` → `handle` and `finally` → `always`

**Improvement opportunity:** The parameter syntax `(Exception e)` reverses the PHP order (`Exception $e`), but follows the PHireScript method parameter convention (`Type name`), so it's internally consistent.

---

### ★★★★★ Union Types

```
String|Null
Ipv4|Ipv6
String|Int|Bool
```

**Why it scores high:**
- Identical to TypeScript union types and PHP 8.0+ union types
- No edge cases, no modifiers

---

### ★★★★☆ Primitive Types and Inference

```
userName = "André"      // String (inferred)
userAge = 25            // Int (inferred)
isActive = true         // Bool (inferred)
```

**Why it scores well:**
- Variable declaration without a type keyword is simple and clean
- An AI might attempt to add `var`, `let`, `const`, or a type prefix — this is the main risk
- Once the rule "no keyword, just `name = value`" is established, generation is reliable

**AI risk:** AIs trained on PHP/TypeScript/JS tend to add `$`, `let`, or `var` prefixes.

---

### ★★★★☆ Method Signatures

```
methodName(Type param, Type param2 = defaultValue): ReturnType {
    return value
}
```

**Why it scores well:**
- Parameter syntax `Type name` (instead of `$name: Type`) is consistent throughout the language
- Return type after `:` mirrors TypeScript
- `= defaultValue` for defaults is universal

**AI risk:** AIs may reverse to `name: Type` (TypeScript order) or `$name` (PHP). Needs one clear example.

---

### ★★★★☆ Abstract Class and Abstract Properties

```
abstract class Repository {
    abstract String tableName

    methodExample(): Null {
        return null
    }
}
```

**Why it scores well:**
- `abstract` is a standard keyword
- `abstract String tableName` (abstract property inline) is clean and predictable
- No special syntax beyond the `abstract` prefix

---

### ★★★★☆ Trait

```
trait Logger {
    log(String msg): String {
        return "message"
    }
}
```

**Why it scores well:**
- `trait` is a standard OOP concept
- Body follows the same method syntax as `class`
- No edge cases in the current implementation

---

### ★★★★☆ SuperTypes as Constructors

```
email = Email('user@example.com')
uuid = Uuid()
color = Color('#fff')
```

**Why it scores well:**
- The pattern is consistent: `SuperType('value')` everywhere
- An AI that learns one SuperType learns all of them
- `Uuid()` with no argument (auto-generate) is clean

**AI risk:** The vocabulary of SuperTypes must be learned (Email, Ipv4, Ipv6, Uuid, Color, Url, Cron, Duration, Json, Mac, Slug, Date). An AI may invent non-existent ones like `Phone` or `Cpf`.

---

### ★★★★☆ Range Syntax

```
1..10     // ascending
2..-10    // descending
```

**Why it scores well:**
- `..` range operator is used in Kotlin, Swift, and Ruby — recognizable
- Negative endpoint for descending range is logical

---

### ★★★☆☆ Access Modifiers (`+`, `#`)

```
type UserCredentials as scoped {
    String login          // protected (default)
    + Date dateBirth      // public
    # Ipv4|Ipv6 lastIp   // private
}
```

**Why it's rated medium:**
- `+` for public and `#` for private are non-standard shortcuts
- An AI will default to writing `public`/`private`/`protected` keywords
- The "no modifier = protected" default is a hidden rule that AI will not guess
- Short symbols make the code terse but harder to generate correctly without examples

**Improvement opportunity:** Consider whether `pub`, `priv` or the full words would be more AI-friendly. The symbols save characters but cost AI predictability.

---

### ★★★☆☆ Method Suffix Conventions (`?`, `!`)

```
save?(Array data): Bool { }    // ? = nullable return / optional to implement
delete!(): Void { }            // ! = void (executes and returns nothing)
```

**Why it's rated medium:**
- Creative and readable for humans
- `?` is ambiguous: does it mean "method may return null" or "method is optional to implement in an interface"? In practice it appears to cover both, which needs explicit documentation.
- `!` means **void** in PHireScript — the method executes an action and returns nothing. This is a **divergence from Ruby**, where `!` means the method mutates the receiver in-place. An AI trained on Ruby will misinterpret this and associate `!` with mutation, not with Void return type.
- An AI will likely omit these suffixes entirely, or apply the wrong Ruby semantics to `!`

**AI risk summary:**
- Ruby-trained AI: will read `!` as "mutates receiver" instead of "returns Void" — high risk of semantic mismatch
- General AI: will omit both suffixes, falling back to standard method syntax

**Improvement opportunity:** The semantics of `?` need to be pinned down. "Method may return null" and "optional interface method" are two different concepts — using the same symbol for both creates ambiguity.

---

### ★★★☆☆ Class Modifier Word Order

```
class AuthenticatorClass with Logger as singleton implements Authenticator, Another { }
```

**Why it's rated medium:**
- The combination `with Trait as Modifier implements Interface` is a unique word order
- Different from Java/PHP (`class Foo extends Bar implements Baz`), TypeScript, or Kotlin
- An AI must memorize: `with` before `as`, `implements` last, `extends` not used in this position
- Each piece is meaningful, but the order is arbitrary enough to cause errors

**Improvement opportunity:** The grammar is `class Name [with Trait] [as modifier] [implements I1, I2]`. If this order were documented as a single template, AIs could fill in the slots reliably.

---

### ★★☆☆☆ Interface Optional Method Prefix (`*`)

```
interface Another {
    * save?(Array data): Bool   // * = optional to implement
    delete!(): Void
    getCompleteUserName(): String|Null
}
```

**Why it's rated low:**
- `*` prefix in an interface is not used in any mainstream language for this purpose
- Combined with `?` suffix, the method has two markers (`* save?`) that overlap in meaning
- An AI will not generate `*` unless explicitly told about it
- Risk: if `?` already means "optional to implement", what does `*` add? Are they duplicates?

**Recommendation:** Clarify whether `*` and `?` in interface context are complementary or redundant. If `?` already implies optional-to-implement, `*` may not be needed. One clear marker is more AI-friendly than two.

---

### ★★☆☆☆ Magic Method Name Mapping

```
onCreate(...)   → __construct
onDestroy(...)  → __destruct
onGet(...)      → __get
toString(...)   → __toString
```

**Why it's rated low:**
- The mapping from PHireScript names to PHP magic methods is not derivable from rules — it's a lookup table
- An AI may invent names like `onInit`, `onBuild`, or `constructor`
- The full table has 14 entries — that's significant vocabulary to memorize

**Mitigation:** This feature requires a reference table in every prompt or system context. It's unavoidable given the mapping goal. The feature itself is well-designed; the challenge is pure vocabulary.

---

## Summary Table

| Feature | Rating | Primary AI Risk |
|---|---|---|
| Package system (`pkg`/`use`/`external`) | ★★★★★ | None |
| `type` / `immutable` | ★★★★★ | None |
| `if` / `else` / `elseif` | ★★★★★ | None |
| `try` / `handle` / `always` | ★★★★★ | None |
| Union types | ★★★★★ | None |
| Variable inference | ★★★★☆ | May add `$`, `var`, or `let` prefix |
| Method signatures | ★★★★☆ | May reverse `Type name` to `name: Type` |
| Abstract class/property | ★★★★☆ | Minor; standard keyword |
| Trait | ★★★★☆ | Minor; standard concept |
| SuperTypes | ★★★★☆ | May invent non-existent SuperTypes |
| Range `..` | ★★★★☆ | Minor; common pattern |
| Access modifiers `+`/`#` | ★★★☆☆ | Will use `public`/`private` keywords instead |
| Method suffix `?`/`!` | ★★★☆☆ | Will omit; `?` meaning is ambiguous |
| Class modifier word order | ★★★☆☆ | Will get `with`/`as`/`implements` order wrong |
| Interface `*` prefix | ★★☆☆☆ | Will not generate; overlaps with `?` |
| Magic method names | ★★☆☆☆ | Pure lookup table; no rule to derive |

---

## Priority Recommendations

Features worth revisiting for improved AI-friendliness (in order of impact):

1. **`?` method suffix ambiguity** — Pin down whether `?` means "nullable return", "optional to implement in interface", or both. If both, document the rule explicitly; if they're the same concept, confirm it.

2. **`*` interface prefix** — Evaluate whether this is redundant with `?`. If it serves a distinct purpose, name it with a keyword instead of a symbol (e.g., `optional save?(...)` is more AI-readable than `* save?(...)`).

3. **Access modifiers `+`/`#`** — Consider `pub`/`priv` short keywords as an alternative. Symbols are terse but require memorization; short keywords are self-documenting.

4. **Magic method table** — This is inherently a vocabulary problem. Ensure the mapping table is always present in any context where an AI generates PHireScript code.
