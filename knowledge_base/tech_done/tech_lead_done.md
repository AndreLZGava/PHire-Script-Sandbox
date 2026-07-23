# Tech Lead Done — PHire-Script-Sandbox Knowledge Base

**Generated:** 2026-05-22
**Scope:** PHire-Script-Sandbox (sandbox root)

## What Was Generated

### Overview files (4)
- `AGENTS.md` — agent entry point with quick orientation table
- `brief.md` — 10-line project summary
- `repo.md` — full directory map, tech stack, execution flow, key commands
- `glossary.md` — 30+ terms covering the PHireScript/sandbox domain
- `index.md` — navigation index linking all artifacts

### Skills (8)
| Skill | What it covers |
|---|---|
| `add-test-case` | Creating new case_N directories with .phs files and CaseValidation.php |
| `run-orchestrator` | bin/stretch CLI, modes, tag filters, output interpretation |
| `write-phirescript` | Full PHireScript syntax: classes, interfaces, traits, types, control flow |
| `validate-compilation` | CaseValidation.php: lifecycle hooks, assertHasMessage, PHPUnit integration |
| `phirescript-types` | Primitives, super types, union types, visibility modifiers |
| `debug-compiler` | Diagnosing assertion failures, compiler errors, wrong output |
| `configure-phirescript-json` | PHireScript.json fields, safe committed state, dev path changes |
| `case-metadata` | Tag/Description/Documentation attributes, tag vocabulary, CLI filtering |

### Reference files (3)
- `reference/documentation.md` — external docs and resource pointers
- `reference/codeStands.md` — PHP and PHireScript coding conventions
- `reference/dependency_graph.toon` — dependency graph (text form, no tooling available)

## Key Findings

1. **Package naming is a critical constraint** — `pkg PHireScript.SamplesN` with exact case number. This is the most common error vector for new cases.

2. **Assertion strings use non-ASCII characters** — `✔` (U+2714) and `→` (U+2192). Copy-paste errors with visually similar ASCII characters cause silent failures.

3. **PHireScript.json is ephemeral during orchestrator runs** — `ConfigModifier` backs it up and restores it per case. Direct compiler invocations bypass this, requiring manual restore.

4. **`src/output/` is a staging area** — the orchestrator copies case files here before compilation. Assertions use `src/output/` prefix, not the original case path.

5. **57 unique tags** documented in the case-metadata skill — the vocabulary is large but inconsistent (e.g., `super-type` and `super-types` coexist; `immutalbe` is a typo that must be preserved for compatibility).

6. **phirescript/ is a separate git repo** — git-ignored in the sandbox. The compiler is developed independently. Do not mix commits.

7. **Two-phase compilation** — `SuccessMode` compiles twice (execute + executeAgain) to test idempotency. CaseValidation has matching lifecycle hooks for both passes.

## Assumptions

- PHP 8.x runtime — confirmed by use of `#[Attribute]` and `IS_REPEATABLE`
- PHPUnit ^12.5 — from `composer.json`
- The compiler (phirescript/) is present at `phirescript/` for the sandbox to work
- Docker is optional — native PHP works directly

## Suggested Next Improvements

1. **Add example files to each skill** — the `examples/` directories exist but are empty. Adding real `.phs` / `CaseValidation.php` files as examples would make the skills more actionable.

2. **Canonicalize the tag vocabulary** — fix `immutalbe` typo in new cases, merge `super-type`/`super-types` into one tag.

3. **Document warning and error modes** — only success mode has real cases (34). Warning and error modes are nearly empty.

4. **Document .pht test syntax** — the `samples/feature/` cases show PHireScript test syntax (`validate`, `test`, `skip` blocks) which isn't documented in any skill.

5. **Dependency graph tooling** — the `.toon` file is a placeholder. A real tool-generated graph would help agents understand cross-case dependencies.
