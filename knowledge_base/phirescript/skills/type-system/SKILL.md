---
name: type-system
description: PHireScript type system internals — primitives, super types, meta types, PHP mapping, method descriptors, and SymbolTableManager
metadata:
  type: skill
---

# Skill: Type System

## Triggers

- "how are types implemented", "add a new super type", "add a new meta type"
- "type methods", "method descriptors", "BaseMethods", "SymbolTableManager"
- "PhpTypeResolver", "how does String.length() compile"
- "TypeResolver", "classify a type", "primitive vs super type"

## When to Use

Use when adding new types to the language, adding methods to existing types, debugging type resolution, or understanding how PHireScript types map to PHP.

## Repository Context

- `TypeResolver` (classification): `src/Helper/TypeResolver.php`
- `PhpTypeResolver` (emission): `src/Emitter/Base/Type/PhpTypeResolver.php`
- `SuperTypes` base: `src/Runtime/Types/SuperTypes.php`
- `MetaTypes` base: `src/Runtime/Types/MetaTypes.php`
- Super type implementations: `src/Runtime/Types/SuperTypes/`
- Meta type implementations: `src/Runtime/Types/MetaTypes/`
- Method descriptors: `src/Runtime/DefaultOverrideMethods/Types/`
- Super type methods: `src/Runtime/DefaultOverrideMethods/SuperTypes/`
- `SymbolTableManager` (auto-loads): `src/Compiler/Parser/Managers/SymbolTableManager.php`
- `GeneralType` (all-types methods): `src/Runtime/DefaultOverrideMethods/GeneralType.php`

## Key Patterns

### Type classification (TypeResolver)

`src/Helper/TypeResolver.php` classifies any type name string:

```php
TypeResolver::isPrimitive('String')    → true   // String, Int, Float, Bool, Array, Object, Void, Null, Mixed, Any
TypeResolver::isSuperType('Email')     → true   // Email, Uuid, Ipv4, Ipv6, Color, Url, etc.
TypeResolver::isMetaType('Date')       → true   // Date, DateTime, Currency, Password, Phone, Time, Card
TypeResolver::isCustom('UserModel')    → true   // anything else = user-defined class
```

### PHP type mapping (PhpTypeResolver)

During emission, `PhpTypeResolver::resolve()` converts PHireScript type to PHP:

| PHireScript      | PHP output       | Notes                          |
|------------------|------------------|--------------------------------|
| `String`         | `string`         | native                         |
| `Int`            | `int`            | native                         |
| `Float`          | `float`          | native                         |
| `Bool`           | `bool`           | native                         |
| `Array`          | `array`          | native                         |
| `Object`         | `object`         | native                         |
| `Void`           | `void`           | return type only               |
| `Null`           | `null`           | in union only                  |
| `Mixed` / `Any`  | `mixed`          | native                         |
| `Email`          | `string`         | supertype → string in PHP      |
| `Uuid`           | `string`         | supertype → string             |
| `Date`           | `Date`           | metatype → class name          |
| `UserModel`      | `UserModel`      | custom → class name as-is      |
| `String\|Null`   | `string\|null`   | null always last in PHP 8      |

### SuperTypes runtime implementation

All super types extend `SuperTypes`:

```php
abstract class SuperTypes
{
    public static function cast(mixed $value): mixed
    {
        if (!static::validate($value)) {
            throw new \InvalidArgumentException("Invalid value for " . static::class);
        }
        return static::transform($value);
    }

    abstract protected static function validate(mixed $v): bool;

    protected static function transform(mixed $v): mixed
    {
        return $v;  // default: return as-is; override for normalization
    }
}
```

Example — `Email`:
```php
class Email extends SuperTypes
{
    protected static function validate(mixed $v): bool
    {
        return filter_var($v, FILTER_VALIDATE_EMAIL) !== false;
    }
}
// Usage: Email::cast('user@example.com') → 'user@example.com' or throws
```

### Adding a new SuperType

1. Create `src/Runtime/Types/SuperTypes/MySuperType.php` extending `SuperTypes`
2. Add to the `T_SUPER_TYPE` token list in `Scanner.php`
3. Add to `TypeResolver::isSuperType()` check list
4. Add to `PhpTypeResolver` mapping (supertype → string)
5. Optionally create `src/Runtime/DefaultOverrideMethods/SuperTypes/MySuperTypeMethods.php`

### Method descriptors (DefaultOverrideMethods)

Every type with chainable methods has a `*Methods.php` class returning descriptors:

```php
class StringMethods
{
    /** @return BaseMethods[] */
    public static function getMethods(): array
    {
        return [
            new BaseMethods(
                name: 'length',
                phpCodeForConversion: 'strlen($self)',
                returnTypes: ['Int'],
                params: [],
                overridesSelfParam: true
            ),
            new BaseMethods(
                name: 'toUpperCase',
                phpCodeForConversion: 'strtoupper($self)',
                returnTypes: ['String'],
                params: [],
                overridesSelfParam: true
            ),
        ];
    }
}
```

`BaseMethods` fields:
- `name` — method name in PHireScript
- `phpCodeForConversion` — PHP code template (`$self` = the receiver)
- `returnTypes` — array of PHireScript return type strings
- `params` — array of `BaseParams` descriptors
- `overridesSelfParam` — whether the method returns `$self` result

### GeneralType — methods on all types

`src/Runtime/DefaultOverrideMethods/GeneralType.php` defines methods available on **every** type:

```
destroy!()     → `unset($var)`
defined?()     → `isset($var)`
getClass()     → `get_class($var)`
show!()        → `var_dump($var)`
display!()     → `print_r($var)`
```

### SymbolTableManager — auto-loading

`SymbolTableManager` auto-loads all `*Methods.php` classes via reflection at startup.
It scans `src/Runtime/DefaultOverrideMethods/` and all sub-directories, discovers classes,
calls `getMethods()`, and registers the method descriptors under their type name.

To resolve method chain `str.length`:
```php
$symbolTableManager->getFunction('String', 'length')
// Returns BaseMethods descriptor or null if not found
```

### Magic methods

PHireScript magic methods map to PHP `__magic` counterparts via `MagicMethods.php`:

```php
// src/Runtime/CustomClasses/MagicMethods.php
const MAGIC_MAP = [
    'onCreate'          => '__construct',
    'onDestroy'         => '__destruct',
    'onGet'             => '__get',
    'onSet'             => '__set',
    'hasHas'            => '__isset',
    'onUnset'           => '__unset',
    'onCall'            => '__call',
    'onStaticCall'      => '__callStatic',
    'toString'          => '__toString',
    'toSerialize'       => '__serialize',
    'beforeSerialize'   => '__sleep',
    'afterUnserialize'  => '__wakeup',
    'onClone'           => '__clone',
    'toInspect'         => '__debugInfo',
];
```

The `MagicMethodDeclarationBinder` detects magic method names and attaches the `phpMagicName` to the `MethodDeclarationNode`. `MethodEmitter` then emits the PHP `__magic` name.

## Critical Rules

1. **New super type = Scanner + TypeResolver + PhpTypeResolver** — all three must be updated or the type is only partially recognized.
2. **SuperType → string in PHP** — super types always map to `string` in PHP emission (they're runtime-validated strings).
3. **MetaType → class name** — meta types emit as their class name in PHP.
4. **`$self` in `phpCodeForConversion`** — always use `$self` as the placeholder for the receiver object in method descriptor templates.
5. **SymbolTableManager auto-loads** — no explicit registration needed; just create the `*Methods.php` file in the right directory.

## Common Mistakes

- Adding a new super type to runtime but forgetting to add it to Scanner → not recognized as `T_SUPER_TYPE`
- Forgetting `TypeResolver` update → type classified as `custom`, emitted as class name instead of `string`
- `BaseMethods::phpCodeForConversion` using wrong placeholder → method chain produces wrong PHP
- New `*Methods.php` placed outside the auto-scan directory → SymbolTableManager doesn't find it

## Validation Checklist

- [ ] New type added to Scanner `T_SUPER_TYPE` or `T_META_TYPE` token list
- [ ] `TypeResolver` checks updated to classify the new type
- [ ] `PhpTypeResolver` maps the new type to correct PHP type
- [ ] Runtime class implements `SuperTypes` or `MetaTypes` base
- [ ] Methods descriptor class created (if type has chainable methods)
- [ ] `*Methods.php` file placed in `src/Runtime/DefaultOverrideMethods/` subtree
- [ ] `composer test` passes; `composer analyse` passes

## Examples

See: [examples/](examples/)
