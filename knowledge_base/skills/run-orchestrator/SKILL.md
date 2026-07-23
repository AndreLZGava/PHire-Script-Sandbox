---
name: run-orchestrator
description: Run bin/stretch to execute test cases, filter by mode and tags, and interpret output
metadata:
  type: skill
---

# Skill: Run Orchestrator

## Triggers

- "run the tests", "run the sandbox", "php bin/stretch", "run all cases"
- "run only the interface cases", "filter by tag", "run case_28"
- "check if case N passes"

## When to Use

Use this skill whenever you need to compile PHireScript cases and validate output, whether running all cases or a targeted subset.

## Repository Context

- Entry point: `bin/stretch`
- Mode handlers: `samples/success/SuccessMode.php`, `samples/warning/WarningMode.php`, `samples/error/ErrorMode.php`
- Case discovery: automatic — all `case_N/` directories under `samples/{mode}/`
- PHPUnit config: `phpunit.xml` (scans `src/compiled/` for `*Test.php`)

## Key Patterns

### Run all modes

```bash
php bin/stretch
```

### Run a specific mode

```bash
php bin/stretch --mode=success
php bin/stretch --mode=warning
php bin/stretch --mode=error
```

### Run multiple modes

```bash
php bin/stretch --mode=success,warning
```

### Filter by tag (AND logic: case must have ALL specified tags)

```bash
php bin/stretch --mode=success --tags=interface
php bin/stretch --mode=success --tags=class,trait
php bin/stretch --mode=success --tags=singleton,methods
```

### Run inside Docker

```bash
make build         # runs php bin/stretch inside container
# or
docker exec -it phirescript_app php bin/stretch --mode=success
```

### Run the compiler directly (no orchestrator)

```bash
# First set the source in PHireScript.json, then:
php phirescript/bin/build
php phirescript/bin/watch      # hot reload
```

## Interpreting Output

**Success line:**
```
✔ src/output/Foo.phs → src/compiled/Foo.php
```

**Failure / error:**
```
✘ src/output/Foo.phs → error message here
```

**Assertion failure** (from assertHasMessage):
```
Exception: Expected message not found in output:
  Expected: "✔ src/output/Foo.phs → src/compiled/Foo.php"
  Got: <actual output>
```

**PHPUnit output** appears after compilation for each case that has `*Test.php`.

## Critical Rules

1. **`PHireScript.json` is backed up and restored per case** — the orchestrator manages this automatically via `ConfigModifier`.
2. **`src/output/` is cleared after each case** — do not rely on state persisting between cases.
3. **Tag filtering uses intersection** — a case matches if any of the case's tags appear in the `--tags` list (not strict AND on all provided tags).
4. **Tags are declared with `#[Tag('...')]`** on `CaseValidation` classes — see [[case-metadata]] skill.
5. **Committed `PHireScript.json` must have `source` pointing into `samples/`** — never commit with `src/output` as source.

## Common Mistakes

- Running `php bin/stretch` from inside a subdirectory — always run from sandbox root
- Forgetting to `composer install` after adding new deps — autoload won't pick up new classes
- Modifying `PHireScript.json` manually without realizing the orchestrator overwrites it during a run
- Expecting tag filter to be exclusive AND — it's intersection (case needs any matching tag)

## Validation Checklist

- [ ] Running from sandbox root (`/home/andre.gava/dev/PHire-Script-Sandbox/`)
- [ ] `vendor/autoload.php` exists (run `composer install` if not)
- [ ] `phirescript/` directory exists and has compiler binaries
- [ ] `PHireScript.json` `source` points into `samples/` (not `src/output`)
- [ ] Tags used in `--tags` match exactly the strings declared in `#[Tag('...')]` attributes

## Examples

See: [examples/](examples/)
