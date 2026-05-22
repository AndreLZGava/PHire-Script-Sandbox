---
name: case-metadata
description: Add and use PHP 8 attributes (Tag, Description, Documentation) on CaseValidation classes for filtering and documentation
metadata:
  type: skill
---

# Skill: Case Metadata

## Triggers

- "add a tag", "filter by tag", "what tags exist", "how do tags work"
- "add description to case", "mark case as documented"
- "#[Tag]", "#[Description]", "#[Documentation]"
- "run only interface cases", "--tags= filter"

## When to Use

Use when creating a new case (add metadata), when running selective case sets (use `--tags=`),
or when searching for all cases covering a specific language feature.

## Repository Context

- Attribute classes: `orchestrator/Attributes/Tag.php`, `Description.php`, `Documentation.php`
- Applied on: `CaseValidation` classes in `samples/success/case_N/CaseValidation.php`
- Consumed by: `Orchestrator::run()` via PHP Reflection
- Filter CLI: `php bin/stretch --mode=success --tags=tag1,tag2`

## Key Patterns

### Tag attribute (repeatable)

```php
#[Tag('interface')]
#[Tag('class')]
#[Tag('methods')]
class CaseValidation extends AbstractCaseValidation { ... }
```

- Multiple `#[Tag(...)]` allowed per class (the attribute is declared `IS_REPEATABLE`)
- Tag values are free-form strings — use existing tags from the list below for consistency
- Case matches `--tags=x,y` if the case has **any** of the requested tags (union, not intersection)

### Description attribute

```php
#[Description('This compiles class with trait, multiple interfaces and diverse return types')]
```

- Single per class
- Shown in orchestrator output when case runs
- Convention: "This compiles <feature description>"

### Documentation attribute

```php
#[Documentation(true)]    // case is documented externally
#[Documentation(false)]   // case is not yet documented
```

- Single per class
- Boolean flag — marks whether the case has external documentation
- Default convention: set `true` for complete, stable cases

### Full attribute block

```php
#[Tag('class')]
#[Tag('trait')]
#[Tag('interface')]
#[Tag('singleton')]
#[Tag('methods')]
#[Documentation(true)]
#[Description('This compiles class with trait, multiple interfaces and diverse return types')]
class CaseValidation extends AbstractCaseValidation
```

### Filtering by tag at CLI

```bash
# Single tag
php bin/stretch --mode=success --tags=interface

# Multiple tags (any match = case runs)
php bin/stretch --mode=success --tags=class,trait

# Combine mode and tag filters
php bin/stretch --mode=success --tags=singleton,methods
```

### Existing tag vocabulary (57 unique tags)

Language constructs:
`abstract-class`, `abstract-property`, `class`, `interface`, `trait`, `type`, `extends`, `methods`, `methods-with-using-params`, `magic-methods`, `constants`, `global-constant`, `variables`

Scopes and patterns:
`singleton`, `default-constructor`, `simple-dto`, `package`, `use`, `reference`, `inference`, `snapshot`

Types:
`primitives`, `types`, `string`, `int`, `float`, `bool`, `array`, `object`, `super-type`, `super-types`

Super types:
`color`, `email`, `url`, `uuid`, `cron`, `duration`, `slug`, `range`

Statements:
`statement`, `if`, `else`, `comparison`, `assignment`, `casting`, `chaining`, `try-handle`, `try-handle-always`, `method-call`

Data formats:
`json`, `literal`, `file-name-compost`, `immutalbe` (typo in codebase — kept for compatibility)

Test flags:
`test`, `documentation`

## Critical Rules

1. **At least one `#[Tag(...)]` is required** — untagged cases cannot be filtered and are invisible when `--tags=` is used.
2. **Use existing tags when the feature matches** — inventing new tags fragments the vocabulary.
3. **Tag values are case-sensitive** — `Interface` ≠ `interface`.
4. **Tag filter is union (any match)** — `--tags=class,trait` runs a case if it has EITHER `class` OR `trait`.
5. **All three attribute `use` imports are required** — otherwise PHP 8 reflection fails silently.

## Common Mistakes

- Forgetting `use PHireScript\Orchestrator\Attributes\Tag;` → PHP fatal at reflection time
- Inventing a new tag that's a near-duplicate of an existing one (e.g., `method` vs `methods`)
- Setting `#[Documentation(false)]` when the case is actually well-documented — misleads doc generation
- Using a single generic tag like `#[Tag('feature')]` — makes filtering useless

## Validation Checklist

- [ ] At least one `#[Tag('...')]` on the class
- [ ] `#[Description('...')]` present
- [ ] `#[Documentation(true|false)]` present
- [ ] Tag values match existing vocabulary (check the list above) or justify a new one
- [ ] All attribute `use` imports present in the file
- [ ] `php bin/stretch --mode=success --tags=<your-tag>` discovers and runs the new case

## Examples

See: [examples/](examples/)
