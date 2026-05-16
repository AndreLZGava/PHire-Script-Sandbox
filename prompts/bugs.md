# PHireScript — Bug Fixes Prompt

You are working on the **PHireScript** compiler, a PHP transpiler located at `phirescript/src/`.
The compiler pipeline is: Scanner → Validator → Parser → Binder → Checker → Emitter → PhpFileGenerator.

Fix the bugs below **one at a time**. For each fix: read the file, apply the change, run
`vendor/bin/phpunit` to verify no tests broke, then `composer quality` before moving on.
Bugs are ordered from most critical to least critical.

---

## Critical

### BUG-01 — SymbolTable type lookup is completely broken (unused loop variable)

**File:** `src/SymbolTable.php` lines ~46–59

**Severity:** Critical — type resolution silently returns `'UNKNOWN'` for all lookups,
meaning the type checker operates on wrong data throughout the entire pipeline.

**Problem:** The `getType()` method loops over `$this->scopes` with index `$i`, but never uses `$i`
inside the loop. Instead it looks up `$this->scopes[$name]`, which is wrong: `$scopes` is a
stack indexed by depth, not by variable name.

```php
// BROKEN
public function getType($name, $linePosition)
{
    for ($i = \count($this->scopes) - 1; $i >= 0; $i--) {
        if (isset($this->scopes[$name][$linePosition])) { // $i never used — always checks index $name
            return $this->scopes[$name][$linePosition];
        }
    }
    return 'UNKNOWN';
}
```

**Fix:** Use `$i` to walk the scope stack from innermost to outermost:

```php
public function getType($name, $linePosition)
{
    for ($i = \count($this->scopes) - 1; $i >= 0; $i--) {
        if (isset($this->scopes[$i][$name][$linePosition])) {
            return $this->scopes[$i][$name][$linePosition];
        }
    }
    return 'UNKNOWN';
}
```

Also verify that `setType()` stores data at `$this->scopes[<current_depth>][$name][$linePosition]`
to match this access pattern.

---

### BUG-02 — Double offset increment in `executeSub()` corrupts token position

**File:** `src/Compiler/Parser/Managers/Builder/SequenceBuilder.php` lines ~337–361

**Severity:** Critical — when a rule matches via a callable, the offset is incremented **twice**,
causing the sequence matcher to skip tokens silently and produce wrong match results.

**Problem:** Inside the `separated` case of `executeSub()`, after a callable match:
1. `$offset += $builder->direction` is applied (line ~342) inside the `is_callable` branch.
2. Then `$offset += $builder->direction` is applied **again** (line ~360) unconditionally after
   the `if/else` block for every iteration.

```php
if (is_callable($rule['match'])) {
    if (!$rule['match']($token)) {
        break;
    }
    $offset += $builder->direction; // ← increment #1
    $steps++;
} else {
    // sub-builder path — offset updated inside executeSub result
}

$matched = true;
$offset += $builder->direction; // ← increment #2 (DUPLICATE for callable path)
$steps++;
```

**Fix:** Remove the first increment inside the `is_callable` branch so the unconditional increment
after the `if/else` is the single source of truth — matching the pattern used in the `match()`
method for the same rule type.

---

## Major

### BUG-03 — SuperType list in Binder is incomplete vs Scanner

**File:** `src/Compiler/Binder.php` line ~130

**Severity:** Major — any SuperType not in this list is categorized as `'unknown'` by the Binder,
causing the Checker and Emitter to produce incorrect output for those types.

**Problem:** The Binder's inline `$superTypes` array is a partial copy of the list in Scanner:

```php
// Binder.php — INCOMPLETE
$superTypes = ['Email', 'Ipv4', 'Ipv6', 'Url'];

// Scanner.php — COMPLETE
'T_SUPER_TYPE' => '/^\b(Email|Ipv4|Ipv6|Uuid|Color|Url|CardNumber|Cron|Cvv|Duration|ExpiryDate|Json|Mac|Slug)\b/',
```

**Missing from Binder:** `Uuid`, `Color`, `CardNumber`, `Cron`, `Cvv`, `Duration`, `ExpiryDate`,
`Json`, `Mac`, `Slug`

**Fix:** Synchronize the Binder list with the Scanner regex. The safest approach is to promote
the list to a constant (see `refactor.md` item 3.1) so it is defined once and referenced from
both places.

---

### BUG-04 — StringMethods parameter name mismatch causes broken PHP output

**File:** `src/Runtime/DefaultOverrideMethods/Types/StringMethods.php` lines ~59–95

**Severity:** Major — the generated PHP code references a placeholder (`@characters`) that is
never declared as a parameter, so the substitution step produces invalid PHP with literal
`@characters` in the output.

**Problem:** Three methods (`removeSpaces`, `removeSpacesLeft`, `removeSpacesRight`) declare a
parameter named `@search` but their code templates reference `@characters`:

```php
// removeSpaces — lines ~59–71
new BaseParams('@search', 'string|null', false, null),   // param name: @search
// ...
'return @characters !== null ? \trim(@self, @characters) : \trim(@self);' // uses @characters ← MISMATCH
```

The same mismatch exists in `removeSpacesLeft` (lines ~73–83) and `removeSpacesRight`
(lines ~85–95).

**Fix:** Rename the parameter declaration to `@characters` in all three methods to match the
code template (or vice versa — pick one name and apply it consistently):

```php
new BaseParams('@characters', 'string|null', false, null),
```

---

### BUG-05 — `getProcessedTokens()` can produce a negative array offset

**File:** `src/Compiler/Parser/Managers/TokenManager.php` lines ~33–39

**Severity:** Major — when called early in parsing (current position < limit), the `array_slice`
start offset becomes negative, returning an unexpected slice of tokens from the end of the array
instead of the beginning.

**Problem:**
```php
public function getProcessedTokens(int $limit = 100): array
{
    return array_slice($this->getTokens(), $this->getCurrentPosition() - $limit, $limit);
    //                                      ↑ can be negative, e.g. position=5, limit=100 → -95
}
```

`array_slice` with a negative offset counts from the end of the array, not from 0.

**Fix:** Clamp the start offset to zero:
```php
public function getProcessedTokens(int $limit = 100): array
{
    $start = max(0, $this->getCurrentPosition() - $limit);
    return array_slice($this->getTokens(), $start, $limit);
}
```

---

## Minor

### BUG-06 — Operator precedence error silently changes validation logic in Checker

**File:** `src/Compiler/Checker.php` lines ~133–135

**Severity:** Minor — due to PHP operator precedence (`&&` binds tighter than `||`), the condition
is evaluated differently from what the indentation suggests, potentially allowing invalid return
types to pass validation.

**Problem:**
```php
// Parsed as: ($prop->mustBeBool && count > 1) || ($prop->mustBeBool && current !== 'Bool')
if ($prop->mustBeBool && \count($returnMethod) > 1 ||
    $prop->mustBeBool && \current($returnMethod) !== 'Bool') {
```

**Fix:** Add explicit parentheses to match intended logic:
```php
if ($prop->mustBeBool && (\count($returnMethod) > 1 || \current($returnMethod) !== 'Bool')) {
```

Same issue exists for the `mustBeVoid` block immediately below.

---

### BUG-07 — `$outputFile` may be used before being set in FileManager

**File:** `src/Compiler/FileManager.php` line ~112

**Severity:** Minor — under specific compilation modes or configurations, execution can reach the
`if (!isset($outputFile))` guard before `$outputFile` has been assigned, causing an undefined
variable warning that is silently swallowed.

**Problem:** `$outputFile` is only assigned inside conditional branches (lines ~102–108). If
neither branch executes, the variable is undefined.

**Fix:** Initialise `$outputFile` to `null` before the conditional block:
```php
$outputFile = null;
// ... conditional assignments ...
if ($outputFile === null) {
    continue;
}
```

---

### BUG-08 — Redundant dual check in `isForbidden()` (logical error)

**File:** `src/Compiler/Validator.php` lines ~119–122

**Severity:** Minor — the method checks the same `$this->forbidden` array twice with different
lookup semantics. `array_key_exists` checks keys; `in_array` checks values. If `$forbidden` is
an associative array (keys = forbidden words, values = metadata), `in_array` never matches, so
the second check is dead code. If it is a flat array (values = forbidden words), `array_key_exists`
never matches, so the first check is dead code. One of the two always returns false.

```php
private function isForbidden(string $word): bool
{
    return \array_key_exists($word, $this->forbidden) ||
        \in_array($word, $this->forbidden, true); // one of these is always false
}
```

**Fix:** Determine the actual structure of `$this->forbidden` and keep only the correct check.
Based on context (it is populated with word strings as keys), the correct check is:
```php
private function isForbidden(string $word): bool
{
    return \array_key_exists($word, $this->forbidden);
}
```
