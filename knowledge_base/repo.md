# Repository Overview — PHire-Script-Sandbox

## Tech Stack

- **Language:** PHP 8.x
- **Test framework:** PHPUnit 12.x
- **Compiler:** PHireScript (local path repo at `phirescript/`)
- **Autoload:** Composer PSR-4
- **Container:** Docker + docker-compose (optional)

## Directory Map

```
PHire-Script-Sandbox/
├── bin/stretch              CLI entry point — runs the orchestrator
├── orchestrator/
│   ├── Orchestrator.php     Scans cases, drives lifecycle, filters by tag
│   ├── AbstractCaseValidation.php  Base class for every CaseValidation.php
│   ├── ModeTest.php         Abstract mode handler (SuccessMode extends this)
│   ├── ConfigModifier.php   Backup/restore PHireScript.json per case
│   ├── FileManager.php      Copy case files to src/output; clear after
│   └── Attributes/          Tag, Description, Documentation PHP attributes
├── samples/
│   ├── success/
│   │   ├── SuccessMode.php  Compiles + captures output, then calls execute()
│   │   └── case_N/         One dir per test case (N = 1..60)
│   │       ├── *.ps        PHireScript source files
│   │       ├── CaseValidation.php
│   │       └── *Test.php   Optional PHPUnit test on compiled output
│   ├── warning/             Warning-mode cases (case_1 exists, no assertions yet)
│   └── error/               Error-mode cases (mode file only, no cases yet)
├── src/
│   ├── output/              Staging area — case files copied here before compile
│   └── compiled/            Compiler writes .php files here; PHPUnit reads here
├── phirescript/             Compiler source (separate git repo, git-ignored)
├── phpscript-vscode/        VS Code extension (separate git repo, git-ignored)
├── PHireScript.json         Compiler config — source/dist/namespace/resolver
├── phpunit.xml              Scans src/compiled/ for *Test.php
├── composer.json            PSR-4 autoload + path repo for phirescript
└── Makefile                 Docker-based targets: up, build, snapshot, watch
```

## Autoload Namespaces

| Namespace                   | Maps to          |
|-----------------------------|------------------|
| `PHireScript\Orchestrator\` | `orchestrator/`  |
| `Sandbox\Samples\`          | `samples/`       |
| `PHireScript\Sandbox\`      | `src/output/`    |
| `PHireScript\`              | `phirescript/src/` |

## Execution Flow (per case)

```
bin/stretch --mode=success --tags=class,trait
    ↓
Orchestrator::run('success', ['class', 'trait'])
    ↓
For each samples/success/case_N/CaseValidation.php:
  1. Reflect attributes → filter by tags
  2. ConfigModifier::backup() — saves PHireScript.json
  3. FileManager::copy(case_N/, src/output/) — stage files
  4. SuccessMode::before() — writes test config to PHireScript.json
  5. SuccessMode::execute() — ob_start → Compiler::compile() → capture output
  6. CaseValidation::execute() — assertHasMessage([...])
  7. CaseValidation::rightAfterFirstExecution()
  8. SuccessMode::executeAgain() — second compile
  9. CaseValidation::after()
 10. SuccessMode::executeTest() — runs phpunit on src/compiled/
 11. ConfigModifier::revert() — restores PHireScript.json
 12. FileManager::clearOutput() — deletes src/output/ contents
```

## Key Commands

```bash
# Run all modes
php bin/stretch

# Run one mode
php bin/stretch --mode=success

# Filter by tag
php bin/stretch --mode=success --tags=interface,trait

# Compile directly (set PHireScript.json source first)
php phirescript/bin/build

# Hot-reload during development
php phirescript/bin/watch

# Inspect tokens/AST
php phirescript/bin/debug samples/success/case_28/AuthenticatorClass.ps

# Generate .psc snapshots
php phirescript/bin/snapshot

# Docker equivalents
make build
make watch
make debug RUN_ARGS=samples/success/case_4/Repository.ps
```

## Committed State Invariants

- `PHireScript.json` `source` must point inside `samples/` when committed
- `phirescript/` is in `.gitignore` — commit it separately in its own repo
- `phpscript-vscode/` is in `.gitignore` — commit it separately in its own repo (remote: `PHire-Script-Extension.git`)
- `src/output/` and `src/compiled/` are transient — do not commit their contents
