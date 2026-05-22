# External Documentation — PHireScript Compiler

## Internal Reference

- **`architecture.md`** — Detailed architecture reference at repo root. Covers pipeline phases, language feature status (Functional/Partial/Sketch), step-by-step "Adding New Features" guide, key file table. **Read this first for any architectural work.**
- **`CLAUDE.md`** — Developer guide at repo root. CLI commands, commit conventions, file extensions, critical areas.
- **`phirescript-doc/`** — Documentation generation tooling (in parent sandbox repo)

## External Libraries

- **nikic/php-parser v5.x** — PHP AST parser used in post-emission processing
  - Docs: https://github.com/nikic/PHP-Parser
  - Used in: `src/Processors/PhpFileGeneratorHandler.php` + `src/Visitor/`

- **PHPUnit ^12.5** — Test framework
  - Docs: https://phpunit.de/documentation.html

- **PHPStan ^2.1** (level 9) — Static analysis
  - Config: `phpstan.neon`
  - Docs: https://phpstan.org/

- **PHP-CS-Fixer ^3.92** — PSR-12 code style
  - Config: `.php-cs-fixer.php`
  - Run: `composer format`

- **Rector ^2.3** — Automated refactoring to PHP 8.2
  - Config: `rector.php`
  - Run: `composer refactor`

- **PHPMD ^2.15** — Mess detection
  - Config: `phpmd.xml`
  - Run: `composer analyse`

## Sandbox Integration

> **This KB lives at `knowledge_base/phirescript/` inside the sandbox repo** — not inside
> `phirescript/` — to keep the compiler repo free of documentation files.

- **Sandbox KB root**: [`../AGENTS.md`](../AGENTS.md) — sibling folder, consumer perspective
  - Covers: CaseValidation patterns, assertHasMessage, orchestrator, `.ps` syntax
- **Sandbox test cases**: `samples/success/case_N/` (sandbox root) — 34 cases
- **Running sandbox tests**: `php bin/stretch --mode=success` (from sandbox root)
