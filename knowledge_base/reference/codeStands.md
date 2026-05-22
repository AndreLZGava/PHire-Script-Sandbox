# Coding Standards — PHire-Script-Sandbox

## PHP Standards

- **PHP version:** 8.x (uses PHP 8 attributes `#[Attribute]`, `#[Tag]`, etc.)
- **PSR-4 autoloading** via Composer — namespace mirrors directory path
- **PHPUnit 12.x** for test assertions
- **No strict_types declaration** required by convention (not enforced in orchestrator)

## Naming Conventions

### Test case directories

```
samples/success/case_N/    N = sequential integer starting at 1
samples/warning/case_N/
samples/error/case_N/
```

### CaseValidation namespace

```php
namespace Sandbox\Samples\success\case_N;   // must mirror path
namespace Sandbox\Samples\warning\case_N;
namespace Sandbox\Samples\error\case_N;
```

### PHireScript package names

```
pkg PHireScript.SamplesN    // N = case folder number (not reused across cases)
```

### PHPUnit test files

```
*Test.php suffix (e.g., RepositoryTest.php, UserTest.php)
namespace PHireScript\Sandbox\src\output;   // matches autoload for src/output/
```

## File Organization

- Each case is **self-contained** — no dependencies on other cases
- A case must declare its own `pkg` with a unique suffix
- Compiled PHP lives in `src/compiled/` — do not edit manually
- `src/output/` is a staging area — do not commit its contents

## Commit Standards

- **No AI co-author** in commit messages — do not add `Co-Authored-By: Claude` or similar
- Commit messages follow conventional commits style: `feat:`, `fix:`, `docs:`, `chore:`
- `phirescript/` is committed separately in its own repo
- `PHireScript.json` must have `source` pointing into `samples/` when committed

## Attribute Conventions

```php
#[Tag('kebab-case-tag')]           // lowercase kebab-case
#[Description('This compiles X')]  // starts with "This compiles"
#[Documentation(true)]             // true for stable, documented cases
```

## Tag Vocabulary

Use existing tags from the 57-tag vocabulary in `skills/case-metadata/SKILL.md` before inventing new ones.
New tags are acceptable only for genuinely new language constructs with no existing equivalent.

## PHireScript Style

- No semicolons at end of statements
- Type names are `PascalCase` (`String`, `Bool`, `Null`)
- Method names are `camelCase`
- Variable names are `camelCase`
- Package names use dot notation: `PHireScript.SamplesN`
- Comments: `//` single line, `/** */` docblock
