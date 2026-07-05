# PHire-Script-Sandbox — Agent Entry Point

This is the knowledge base for the **PHire-Script-Sandbox** repository.
The sandbox validates PHireScript (`.ps`) compilation to PHP using an orchestrator framework.

## Agent Roles

The sandbox defines AI agent roles that collaborate on the project. Each agent has a folder under `agents/` with its definition and working memory.

| Agent | Role | Talks to user? |
|---|---|---|
| `agents/phirescript-architect/` | Language design, orchestration, AI-first tooling | Primary interface |
| `agents/php-architect/` | PHP output quality, transpiler code quality — safety/performance counterweight | On disagreements |
| `agents/qa/` | Spec review, edge cases, test validation | Rarely |
| `agents/documentador/` | Feature docs, bug docs, language docs, `phirescript-doc/` | When consulted |
| `agents/pm/` | Backlog prioritization, feature vs bug triage | When consulted |
| `agents/developer-compiler/` | Implementation in transpiler and sandbox | Never directly |
| `agents/developer-extension/` | Implementation in VS Code extension | Rarely |

See `agents/README.md` for the full communication structure.

## Quick Orientation

| I want to…                                | Go to                                                          |
|-------------------------------------------|----------------------------------------------------------------|
| Implement or design a PHireScript feature | `skills/implement-phirescript-feature/SKILL.md`               |
| Add a new test case                       | `skills/add-test-case/SKILL.md`                               |
| Run the test suite                        | `skills/run-orchestrator/SKILL.md`                            |
| Write PHireScript source files            | `skills/write-phirescript/SKILL.md`                           |
| Write CaseValidation.php assertions       | `skills/validate-compilation/SKILL.md`                        |
| Understand the type system                | `skills/phirescript-types/SKILL.md`                           |
| Debug a compilation failure               | `skills/debug-compiler/SKILL.md`                              |
| Change source/dist paths in config        | `skills/configure-phirescript-json/SKILL.md`                  |
| Add or filter by tags                     | `skills/case-metadata/SKILL.md`                               |

## Key Files

| File                        | Purpose                                          |
|-----------------------------|--------------------------------------------------|
| `bin/stretch`               | Orchestrator CLI entry point                     |
| `orchestrator/`             | Framework: Orchestrator, AbstractCaseValidation  |
| `samples/success/case_N/`   | Test cases (48 cases currently); error cases in `samples/error/` |
| `PHireScript.json`          | Compiler configuration                           |
| `composer.json`             | Autoload map, scripts                            |
| `phpunit.xml`               | PHPUnit config — scans `src/compiled/`           |

## PHireScript Compiler Internals

The compiler that powers this sandbox lives at `phirescript/`.
Its knowledge base is kept here (not inside `phirescript/`) to avoid polluting the compiler repo.

| I want to…                                      | Go to                                                               |
|-------------------------------------------------|---------------------------------------------------------------------|
| Understand the compilation pipeline             | `phirescript/skills/compilation-pipeline/SKILL.md`                 |
| Add a new language construct to the compiler    | `phirescript/skills/add-language-feature/SKILL.md`                 |
| Understand the parser (Context+Resolver+Node)   | `phirescript/skills/parser-context-resolver/SKILL.md`              |
| Write a new NodeEmitter                         | `phirescript/skills/write-emitter/SKILL.md`                        |
| Write a Binder or Checker pass                  | `phirescript/skills/write-binder-checker/SKILL.md`                 |
| Understand the compiler type system             | `phirescript/skills/type-system/SKILL.md`                          |
| Debug a failure inside the compiler             | `phirescript/skills/debug-compiler/SKILL.md`                       |
| Run the compiler's unit tests                   | `phirescript/skills/run-tests/SKILL.md`                            |
| Understand tokens and the scanner               | `phirescript/skills/scanner-tokens/SKILL.md`                       |

→ Full compiler KB entry point: [phirescript/AGENTS.md](phirescript/AGENTS.md)

## VS Code Extension

The VS Code extension lives at `phpscript-vscode/` — a separate git repo inside the sandbox.
It provides syntax highlighting, linting, formatting, hover docs, and custom icons for `.ps` and `.pst` files.
The sandbox is its shell: all language spec, compiler internals, and sandbox cases are available as context when working on the extension.

| I want to…                                      | Go to                                           |
|-------------------------------------------------|-------------------------------------------------|
| Understand what the extension does              | `phpscript-vscode/README.md`                    |
| See the grammar / syntax highlighting rules     | `phpscript-vscode/syntaxes/`                    |
| See the linting / validation logic              | `phpscript-vscode/src/`                         |
| Check the extension manifest                   | `phpscript-vscode/package.json`                 |
| Run or build the extension                     | `phpscript-vscode/` — `npm install && npm run compile` |

## Read Next

- [brief.md](brief.md) — 10-line project summary
- [repo.md](repo.md) — Structure, tech stack, execution flow
- [glossary.md](glossary.md) — Key terms
