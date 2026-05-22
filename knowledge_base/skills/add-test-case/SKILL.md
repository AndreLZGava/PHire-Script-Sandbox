---
name: add-test-case
description: Create a new test case directory under samples/success, samples/warning, or samples/error with .ps source files and CaseValidation.php
metadata:
  type: skill
---

# Skill: Add Test Case

## Triggers

- "add a new case", "create case_N", "add success case", "write a test for feature X"
- User asks to validate a new PHireScript feature in the sandbox

## When to Use

Use this skill whenever you need to prove that a PHireScript language feature compiles correctly.
Each case is self-contained: source files + one validation class.

## Repository Context

- Cases live at: `samples/success/case_N/` (N = next available integer, currently 35+)
- The orchestrator auto-discovers all `case_N/` directories — no registration needed
- Each case needs: at least one `.ps` file + `CaseValidation.php`
- Optional: a `*Test.php` PHPUnit file to assert compiled PHP behavior

## Key Patterns

### 1. Determine the case number

```bash
ls samples/success/ | sort -V | tail -5
# Pick the next N after the last existing case
```

### 2. Create the directory and source file

```
samples/success/case_N/
├── MyFeature.ps           ← PHireScript source (see write-phirescript skill)
└── CaseValidation.php     ← orchestration + assertions
```

### 3. Package naming rule (CRITICAL)

Every `.ps` file in `case_N/` must declare:

```
pkg PHireScript.SamplesN
```

Where `N` is the exact case number. Using a wrong number causes cross-case namespace collisions in the shared web environment.

### 4. CaseValidation.php skeleton

```php
<?php

namespace Sandbox\Samples\success\case_N;

use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\Attributes\Tag;
use PHireScript\Orchestrator\Attributes\Description;
use PHireScript\Orchestrator\Attributes\Documentation;

#[Tag('your-tag')]
#[Documentation(true)]
#[Description('This compiles <what this case tests>')]
class CaseValidation extends AbstractCaseValidation
{
    public function execute()
    {
        $this->assertHasMessage([
            "✔ src/output/MyFeature.ps → src/compiled/MyFeature.php",
        ]);
    }
}
```

### 5. Assertion message format

The success message emitted by the compiler follows exactly:

```
✔ src/output/{FileName}.ps → src/compiled/{FileName}.php
```

The filename in the assertion must match the `.ps` filename exactly (case-sensitive).

### 6. Optional: PHPUnit test file

If the feature needs runtime behavior validation, add `MyFeatureTest.php`:

```php
<?php

namespace PHireScript\Sandbox\src\output;

use PHPUnit\Framework\TestCase;

class MyFeatureTest extends TestCase
{
    public function test_something(): void
    {
        // Test compiled PHP behavior
        $this->assertTrue(true);
    }
}
```

Set `$this->stopIfNoTest = true;` in `CaseValidation::execute()` if a test is required.

## Critical Rules

1. **Package name = `PHireScript.SamplesN`** where N is the case number. Never use generic suffixes.
2. **Namespace in CaseValidation** = `Sandbox\Samples\success\case_N` (must match directory path).
3. **Assertion string uses `src/output/` prefix** (not the actual case path) — the orchestrator copies files there first.
4. **Never commit `src/output/` or `src/compiled/`** contents — they are transient.
5. **File name in assertion must match .ps filename exactly** — the compiler is case-sensitive.

## Common Mistakes

- Using `PHireScript.Samples` (generic) instead of `PHireScript.Samples28` → breaks web environment
- Assertion path wrong: using `samples/success/case_N/Foo.ps` instead of `src/output/Foo.ps`
- Forgetting `use` imports in CaseValidation.php (AbstractCaseValidation, Tag, Description, Documentation)
- Namespace mismatch between directory `case_28` and declared `namespace ... case_29`

## Validation Checklist

- [ ] `case_N/` directory created under `samples/success/`
- [ ] `.ps` file declares `pkg PHireScript.SamplesN` with correct N
- [ ] `CaseValidation.php` namespace matches `Sandbox\Samples\success\case_N`
- [ ] `assertHasMessage()` uses `src/output/FileName.ps → src/compiled/FileName.php`
- [ ] Tags are meaningful and not duplicated across `#[Tag(...)]` on the same class
- [ ] `php bin/stretch --mode=success --tags=your-tag` passes

## Examples

See: [examples/](examples/)
