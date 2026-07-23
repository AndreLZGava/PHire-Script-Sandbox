---
name: debug-compiler
description: Diagnose and fix compilation failures — wrong output, missing messages, AST/token inspection
metadata:
  type: skill
---

# Skill: Debug Compiler

## Triggers

- "case not passing", "assertion failing", "expected message not found"
- "compiler error", "compilation fails", "wrong PHP output"
- "how to debug PHireScript", "inspect AST", "check tokens"
- "case_N fails", "✘ in output"

## When to Use

Use when a case fails in `bin/stretch`, when the compiler emits an unexpected message, or when you want to inspect what the compiler is doing with a `.phs` file.

## Repository Context

- Compiler binaries: `phirescript/bin/{build,debug,snapshot,watch}`
- Compiler config: `PHireScript.json` (must be set to correct source before running directly)
- Output captured from: `src/compiled/` (compiled PHP) and `src/output/` (staged source)
- Case assertions: `samples/success/case_N/CaseValidation.php`

## Key Patterns

### Step 1 — Point the compiler at the failing case

```bash
# Edit PHireScript.json to set source to the failing case
# (do NOT commit this change)
{
    "paths": {
        "source": "samples/success/case_28",
        "dist": "src/output"
    }
}
```

### Step 2 — Compile and see raw output

```bash
php phirescript/bin/build
```

Check what messages the compiler actually emits. Compare against what `assertHasMessage` expects.

### Step 3 — Inspect tokens

```bash
php phirescript/bin/debug samples/success/case_28/AuthenticatorClass.phs
```

Shows tokenization — useful when the compiler silently ignores a construct.

### Step 4 — Generate a snapshot (.phc)

```bash
php phirescript/bin/snapshot
```

Generates `.phc` files in the source directory showing the intermediate parsed form.

### Step 5 — Read compiled PHP

After `php phirescript/bin/build`, inspect `src/output/` (or `src/compiled/` if dist is set there):

```bash
cat src/output/FileName.php
```

Verify the compiled PHP matches what the `.phs` file was supposed to produce.

### Step 6 — Run PHPUnit directly

```bash
vendor/bin/phpunit --colors=never
```

If a `*Test.php` test is failing, this isolates it from the orchestrator noise.

### Common failure patterns

**Pattern 1: assertHasMessage fails — wrong characters**
```
Expected: "✔ src/output/Foo.phs → src/compiled/Foo.php"
Got: "✓ src/output/Foo.phs -> src/compiled/Foo.php"
```
Fix: The `✔` is U+2714 (not U+2713), `→` is U+2192 (not ASCII `->`). Copy from a passing case.

**Pattern 2: assertHasMessage fails — wrong prefix**
```
Expected: "✔ samples/success/case_28/Foo.phs → ..."
Got: "✔ src/output/Foo.phs → ..."
```
Fix: Assertion must use `src/output/` prefix, not the original case path.

**Pattern 3: Compiler doesn't emit success line — compile error**
```
✘ src/output/Foo.phs → Unexpected token 'X' at line N
```
Fix: Check `.phs` syntax. Use `php phirescript/bin/debug` to inspect tokens at the failing line.

**Pattern 4: Case passes but PHPUnit test fails**
```
FAILURES!
Tests: 1, Assertions: 1, Failures: 1.
```
Fix: The compiled PHP runs but produces wrong output. Read `src/compiled/FooTest.php` and `src/compiled/Foo.php`. The issue is in the compiled PHP logic, not the `.phs` syntax.

**Pattern 5: Namespace conflict — class not found**
```
PHP Fatal error: Class 'PHireScript\Sandbox\src\output\Foo' not found
```
Fix: The `*Test.php` test uses `PHireScript\Sandbox\src\output` as its namespace (per autoload map). The compiled PHP uses `PHireScript\Sandbox` (from `PHireScript.json` `namespace` field).

**Pattern 6: Wrong package name**
```
pkg PHireScript.Samples         // generic — conflicts with other cases
pkg PHireScript.Samples28       // correct — isolated to case_28
```

### Watch mode for iterative development

```bash
php phirescript/bin/watch
# OR
make watch
```

Hot-reloads compilation every time a `.phs` file changes. Useful when iteratively fixing syntax.

## Critical Rules

1. **Restore `PHireScript.json` after manual debugging** — the orchestrator backs up/restores it automatically, but direct compiler runs do not.
2. **`src/output/` and `src/compiled/` are transient** — never commit their contents or rely on them persisting.
3. **ANSI codes in output** — `assertHasMessage` strips ANSI. When reading raw compiler output in terminal, colors are included. Strip with `| sed 's/\x1b\[[0-9;]*m//g'` if comparing manually.

## Validation Checklist

- [ ] `PHireScript.json` restored to `samples/` source after manual debugging
- [ ] Assertion strings use correct UTF-8 characters (`✔` U+2714, `→` U+2192)
- [ ] Assertion uses `src/output/` prefix (not case path)
- [ ] `.phs` file has correct `pkg` declaration
- [ ] Compiled PHP in `src/compiled/` matches expected structure

## Examples

See: [examples/](examples/)
