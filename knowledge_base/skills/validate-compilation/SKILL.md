---
name: validate-compilation
description: Write or fix CaseValidation.php files — lifecycle hooks, assertHasMessage, PHPUnit integration
metadata:
  type: skill
---

# Skill: Validate Compilation

## Triggers

- "write CaseValidation.php", "add assertions", "fix CaseValidation", "case not passing"
- "assertHasMessage not matching", "how to assert compiler output"
- "add a PHPUnit test to a case", "stopIfNoTest"

## When to Use

Use when creating or debugging the assertion logic for a sandbox test case.
`CaseValidation.php` is the only required PHP file per case.

## Repository Context

- Base class: `orchestrator/AbstractCaseValidation.php`
- Attribute classes: `orchestrator/Attributes/{Tag,Description,Documentation}.php`
- Namespace pattern: `Sandbox\Samples\success\case_N` (mirrors directory path)
- Real examples: `samples/success/case_28/CaseValidation.php`, `samples/success/case_4/CaseValidation.php`

## Key Patterns

### Minimal CaseValidation.php

```php
<?php

namespace Sandbox\Samples\success\case_N;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Tag;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('your-tag')]
#[Documentation(true)]
#[Description('This compiles <feature description>')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/FileName.ps → src/compiled/FileName.php",
        ]);
    }
}
```

### Asserting multiple files in one case

```php
public function execute()
{
    $this->assertHasMessage([
        "✔ src/output/Logger.ps → src/compiled/Logger.php",
        "✔ src/output/User.ps → src/compiled/User.php",
        "✔ src/output/AuthenticatorClass.ps → src/compiled/AuthenticatorClass.php",
    ]);
}
```

### Requiring a PHPUnit test

```php
public function execute()
{
    $this->stopIfNoTest = true;     // case fails if *Test.php is absent in compiled output
    $this->assertHasMessage([
        "✔ src/output/Repository.ps → src/compiled/Repository.php",
    ]);
}
```

### Lifecycle hooks

```php
public function before()
{
    // Called before compilation — use to set up state
}

public function execute()
{
    $this->assertHasMessage([...]);
}

public function rightAfterFirstExecution()
{
    // Called after first compile, before second compile
}

public function executeAgain()
{
    // Called during second compilation pass (idempotency check)
    $this->assertHasMessage([...]);
}

public function after()
{
    // Called after both compiles, before PHPUnit
}
```

### How assertHasMessage works

`assertHasMessage(array $expected)`:
1. Takes the captured `$this->output` (compiler stdout)
2. Strips ANSI escape codes
3. Trims each line
4. Checks that every string in `$expected` appears in the output
5. Throws `Exception` with diff if any expected string is missing

The assertion is substring-based — the expected string just needs to appear somewhere in the output line.

### Success message format

The compiler emits exactly:
```
✔ src/output/{FileName}.ps → src/compiled/{FileName}.php
```

- The `✔` character is a UTF-8 checkmark (U+2714)
- `src/output/` is always the prefix (not the original case path)
- Arrow is ` → ` (space + U+2192 + space)

## Critical Rules

1. **Namespace must match directory** — `case_28/` → `namespace Sandbox\Samples\success\case_28`
2. **assertHasMessage uses `src/output/` prefix** — not `samples/success/case_N/`
3. **All three `use` imports are needed** — Tag, Description, Documentation, AbstractCaseValidation
4. **`✔` is not a regular ASCII checkmark** — copy from an existing CaseValidation or use the exact UTF-8 character
5. **`→` is not ASCII `->`** — it's U+2192. Copy from existing assertion strings.

## Common Mistakes

- Using ASCII `->` instead of `→` in assertion → assertion never matches
- Using `✓` (U+2713) instead of `✔` (U+2714) → assertion never matches
- Wrong namespace (e.g., `case_29` when in `case_28/` folder)
- Missing `use` imports for attribute classes → PHP 8 attribute reflection fails
- Asserting the wrong filename (e.g., `Authenticator.ps` when file is `AuthenticatorClass.ps`)

## Validation Checklist

- [ ] Namespace matches directory path exactly
- [ ] All four `use` imports present
- [ ] At least one `#[Tag(...)]` attribute
- [ ] `assertHasMessage` uses `✔` (U+2714) and `→` (U+2192)
- [ ] Filename in assertion matches `.ps` filename exactly
- [ ] `php bin/stretch --mode=success --tags=your-tag` passes without exceptions

## Examples

See: [examples/](examples/)
