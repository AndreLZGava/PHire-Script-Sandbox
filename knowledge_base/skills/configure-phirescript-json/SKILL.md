---
name: configure-phirescript-json
description: Set PHireScript.json source/dist paths for development, understand what each field does, and avoid committing wrong state
metadata:
  type: skill
---

# Skill: Configure PHireScript.json

## Triggers

- "change source path", "point compiler at case_N", "PHireScript.json configuration"
- "what does namespace/resolver/currency do", "dev mode", "paths config"
- "PHireScript.json committed wrong value"

## When to Use

Use when you need to run the compiler directly against a specific case (not via the orchestrator),
or when you want to understand what each field in `PHireScript.json` controls.

## Repository Context

- Config file: `PHireScript.json` at sandbox root
- Read by: PHireScript compiler (`phirescript/bin/build`, `phirescript/bin/watch`, etc.)
- Managed during orchestration: `orchestrator/ConfigModifier.php` backs up and restores it per case
- Committed state must have `source` pointing into `samples/`

## Key Patterns

### Committed (safe) state

```json
{
    "paths": {
        "source": "samples",
        "dist": "src/compiled"
    }
}
```

Or more specifically for a single case (DO NOT COMMIT):
```json
{
    "paths": {
        "source": "samples/success/case_28",
        "dist": "src/output"
    }
}
```

### Full config with all fields

```json
{
    "dev": true,
    "namespace": "PHireScript\\Sandbox",
    "currency": "USD",
    "resolver": "custom",
    "paths": {
        "source": "src/output",
        "dist": "src/compiled",
        "test": "src/tests"
    },
    "generated_at": "2026-01-16 00:31:33"
}
```

### Field reference

| Field              | Purpose                                                                     | Values                        |
|--------------------|-----------------------------------------------------------------------------|-------------------------------|
| `dev`              | Enable development mode (verbose errors, no caching)                        | `true` / `false`              |
| `namespace`        | PHP namespace prefix for compiled output classes                             | e.g., `PHireScript\\Sandbox`  |
| `currency`         | Default currency for money super types                                      | e.g., `USD`, `BRL`            |
| `resolver`         | DI container adapter strategy                                               | `custom`, `laravel`, `symfony`|
| `paths.source`     | Directory the compiler reads `.phs` files from                               | Relative path from root       |
| `paths.dist`       | Directory the compiler writes `.php` files to                               | Relative path from root       |
| `paths.test`       | Directory for compiled test files (`.pht` → `.php`)                        | Relative path from root       |
| `generated_at`     | Timestamp of last config generation (informational)                         | ISO datetime string           |

### Pointing compiler at a specific case (for development)

```bash
# 1. Edit PHireScript.json temporarily
{
    "paths": {
        "source": "samples/success/case_35",
        "dist": "src/output"
    }
}

# 2. Compile
php phirescript/bin/build

# 3. Restore to committed state before git operations
{
    "paths": {
        "source": "samples",
        "dist": "src/compiled"
    }
}
```

### What the orchestrator sets (SuccessMode::before)

Before each case run, `SuccessMode` writes this to `PHireScript.json`:
```json
{
    "dev": true,
    "namespace": "PHireScript\\Sandbox",
    "currency": "USD",
    "resolver": "custom",
    "paths": {
        "source": "src/output",
        "dist": "src/compiled"
    }
}
```

`src/output` is the staging directory where the orchestrator copies case files.

### Check current state before committing

```bash
cat PHireScript.json | grep -A3 '"paths"'
# source must be under samples/, not src/output
```

## Critical Rules

1. **Never commit `source: "src/output"`** — that's the transient staging path, not a persistent source.
2. **Committed `source` must point inside `samples/`** — either `"samples"` (all) or `"samples/success/case_N"` for a single case.
3. **The orchestrator manages this file during runs** — do not manually edit it while `bin/stretch` is running.
4. **`ConfigModifier::revert()` restores on test completion** — if orchestrator crashes, you may need to manually restore.
5. **`dist` and `test` paths are relative to sandbox root** — they must be writable directories.

## Common Mistakes

- Committing with `"source": "src/output"` → other developers can't compile from source
- Forgetting to revert after manual debugging → next orchestrator run uses wrong source
- Using absolute paths in `paths.*` → breaks when repo is cloned to a different location

## Validation Checklist

- [ ] `PHireScript.json` `source` points to a path inside `samples/` before any commit
- [ ] `dist` path exists (create it if needed: `mkdir -p src/compiled`)
- [ ] Manual edits reverted to committed state after development session
- [ ] `git diff PHireScript.json` shows only expected changes before committing

## Examples

See: [examples/](examples/)
