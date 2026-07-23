---
name: scanner-tokens
description: Scanner (lexer) internals — token types, regex patterns, Token class, and how to add or fix token recognition
metadata:
  type: skill
---

# Skill: Scanner and Tokens

## Triggers

- "add a new token", "token not recognized", "T_KEYWORD", "T_SUPER_TYPE"
- "Scanner.php", "tokenize", "how does the lexer work"
- "token type", "Token class", "isKeyword()", "isSuperType()"
- New keyword or symbol is being parsed as `T_IDENTIFIER` when it shouldn't be

## When to Use

Use when adding a new language keyword/symbol, debugging token misclassification, or understanding the lexer output fed to the Parser.

## Repository Context

- Scanner: `src/Compiler/Scanner.php` — `tokenize(string $source): Token[]`
- Token class: `src/Compiler/Parser/Managers/Token/Token.php`
- Modifiers transform: `src/Compiler/Parser/Transformers/ModifiersTransform.php`

## Key Patterns

### Token types reference

| Token Type            | What it matches                                                                 |
|-----------------------|---------------------------------------------------------------------------------|
| `T_COMMENT`           | `// line comment` or `/* block */` or `/** docblock */`                         |
| `T_STRING_LIT`        | `"double quoted"` or `'single quoted'` strings                                  |
| `T_RANGE`             | `1..10`, `-5..-1` (number range literals)                                       |
| `T_NUMBER`            | `42`, `3.14` (integer and float literals)                                       |
| `T_DEPENDENCY_SCOPE`  | `singleton`, `scoped`, `transient`, `newable` (lifecycle keywords)              |
| `T_KEYWORD`           | All language keywords: `class`, `interface`, `trait`, `type`, `pkg`, `use`, …  |
| `T_MAGIC_METHODS`     | `onCreate`, `onDestroy`, `onGet`, `onSet`, `toString`, `toSerialize`, …         |
| `T_BOOL`              | `true`, `false`                                                                 |
| `T_NULL`              | `null`                                                                          |
| `T_PRIMITIVE`         | `String`, `Int`, `Float`, `Bool`, `Object`, `Array`, `Void`, `Null`, `Mixed`, `Any`, `List`, `Map`, `Queue`, `Stack` |
| `T_SUPER_TYPE`        | `Email`, `Ipv4`, `Ipv6`, `Uuid`, `Color`, `Url`, `CardNumber`, `Cron`, `Cvv`, `Duration`, `ExpiryDate`, `Json`, `Mac`, `Slug` |
| `T_META_TYPE`         | `Card`, `Currency`, `Date`, `DateTime`, `Password`, `Phone`, `Time`             |
| `T_CONST`             | `CONSTANT_NAME` — all uppercase with underscores                                |
| `T_IDENTIFIER`        | `camelCase`, `PascalCase` identifiers (and identifiers with `!` or `?` suffix)  |
| `T_SYMBOL`            | Single-char symbols: `{`, `}`, `(`, `)`, `;`, `,`, `:`, `=`, `+`, `<`, `>`, `#`, `!`, `?`, `[`, `]`, `.`, `$`, `*`, `/`, `%`, `|`, `-` |
| `T_MODIFIER`          | Multi-char operators: `->`, `=>`, `::`, `...`, `++`, `--`, `===`, `!==`, `==`, `!=`, `<=`, `>=`, `&&`, `||` |
| `T_ACCESSORS`         | Getter/setter syntax: `+>`, `<>`, `#>`, `*>`, `+<`, `><`, `#<`, `*<`          |
| `T_BACKSLASH`         | `\\` (namespace separator in external declarations)                             |
| `T_EOL`               | `\r\n` or `\n` line breaks                                                      |
| `T_WHITESPACE`        | Spaces and tabs (discarded during tokenization)                                 |

Test-only tokens (`.pht` files):
| `T_TEST_KEYWORD`      | `validate`, `skip`, `test`                                                      |
| `T_TEST_HOOKS`        | `beforeAll`, `beforeEach`, `afterAll`, `afterEach`                              |

### Token class API

```php
class Token
{
    public readonly string $type;    // T_KEYWORD, T_IDENTIFIER, etc.
    public readonly mixed  $value;   // actual string value
    public readonly int    $line;    // 1-based line number
    public readonly int    $column;  // 1-based column number
    public ?string $processedBy;     // set by Parser: which Resolver consumed it
}
```

**Semantic helpers (commonly used in Resolvers):**

```php
$token->isKeyword()          // type === T_KEYWORD
$token->isPrimitive()        // type === T_PRIMITIVE
$token->isSuperType()        // type === T_SUPER_TYPE
$token->isMetaType()         // type === T_META_TYPE
$token->isMagicMethod()      // type === T_MAGIC_METHODS
$token->isIdentifier()       // type === T_IDENTIFIER
$token->isVariable()         // type === T_IDENTIFIER (alias)
$token->isConstant()         // type === T_CONST
$token->isModifier()         // type === T_MODIFIER
$token->isSymbol()           // type === T_SYMBOL
$token->isComment()          // type === T_COMMENT
$token->isStringLiteral()    // type === T_STRING_LIT
$token->isNumber()           // type === T_NUMBER
$token->isBool()             // type === T_BOOL
$token->isNull()             // type === T_NULL
$token->isRange()            // type === T_RANGE
$token->isAccessor()         // type === T_ACCESSORS
$token->isDependencyScope()  // type === T_DEPENDENCY_SCOPE
$token->isMathOperator()     // +, -, *, /, %, =
$token->isBooleanOperator()  // &&, ||, !, ===, !==, ==, !=
$token->isCleanTernary()     // ? :  (ternary operator tokens)
```

### Adding a new keyword

```php
// src/Compiler/Scanner.php
private array $keywords = [
    'class', 'interface', 'trait', 'type',
    'pkg', 'use', 'external',
    'if', 'elseif', 'else', 'return',
    'try', 'handle', 'always',
    'abstract', 'readonly', 'extends', 'implements', 'with',
    'var', 'const',
    'MY_NEW_KEYWORD',  // ← add here
];
```

**Order matters** — keywords are checked before `T_IDENTIFIER`, so a new keyword will no longer match as an identifier. This may break existing code using it as a variable name.

### Adding a new super type

```php
// src/Compiler/Scanner.php
private array $supertypes = [
    'Email', 'Uuid', 'Ipv4', 'Ipv6', 'Color', 'Url',
    'CardNumber', 'Cron', 'Cvv', 'Duration', 'ExpiryDate',
    'Json', 'Mac', 'Slug',
    'MyNewSuperType',  // ← add here
];
```

Also update `src/Helper/TypeResolver.php` to classify it correctly.

### Inspecting tokenization

```bash
php bin/debug path/to/file.phs
```

The first section of output shows the `Token[]` array with type, value, line, and column for every token in the file.

### ModifiersTransform (pre-parser)

`src/Compiler/Parser/Transformers/ModifiersTransform.php` runs in `Validator::validate()` before parsing.
It rewrites tokens:

```
T_SYMBOL '+' (as modifier)  → T_KEYWORD 'protected'
T_SYMBOL '#' (as modifier)  → T_KEYWORD 'private'
T_SYMBOL '~' (as modifier)  → T_KEYWORD 'abstract'    (in some contexts)
```

Context-dependent: `+` is only rewritten when in a class body modifier position, not in math expressions.

## Critical Rules

1. **Regex order is first-match wins** — the Scanner iterates patterns in array order. More specific patterns (keywords, super types) must come before `T_IDENTIFIER`.
2. **`T_WHITESPACE` and `T_EOL` are filtered** — the Scanner discards these before returning the token array. Parsers never see whitespace tokens.
3. **`T_COMMENT` tokens ARE in the stream** — Contexts must explicitly skip or handle them. Failing to handle a comment inside a class body causes the parser to stall.
4. **`T_CONST` is all-uppercase** — regex: `/^[A-Z][A-Z0-9_]*\b/`. Variables starting with uppercase but containing lowercase are `T_IDENTIFIER`.
5. **`!` and `?` suffixes on identifiers** — `save?` and `delete!` are tokenized as a single `T_IDENTIFIER` with value `save?` / `delete!`. The `NodeEmitterAbstract::removeEndPunctuation()` strips them during emission.

## Common Mistakes

- Adding a new keyword but not checking for T_COMMENT handling in its Context → parser stalls on inline comments
- Super type name matches start of another identifier (e.g., `Email` matches start of `EmailAddress`) — regex uses `\b` word boundary to prevent this
- Forgetting to update `TypeResolver` after adding to the Scanner → token classified as `T_SUPER_TYPE` but `TypeResolver::isSuperType()` returns false

## Validation Checklist

- [ ] New keyword/super type added to the correct array in `Scanner.php`
- [ ] `php bin/debug` shows the new token with correct `T_*` type
- [ ] Corresponding `TypeResolver` helper updated if it's a type
- [ ] No existing identifiers shadow the new keyword (run existing tests)
- [ ] `composer test` passes
- [ ] `composer analyse` passes

## Examples

See: [examples/](examples/)
