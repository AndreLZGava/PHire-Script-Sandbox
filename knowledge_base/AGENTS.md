# PHire-Script-Sandbox — Agent Entry Point

This is the knowledge base for the **PHire-Script-Sandbox** repository.
The sandbox validates PHireScript (`.ps`) compilation to PHP using an orchestrator framework.

## Quick Orientation

| I want to…                                | Go to                                        |
|-------------------------------------------|----------------------------------------------|
| Add a new test case                       | `skills/add-test-case/SKILL.md`              |
| Run the test suite                        | `skills/run-orchestrator/SKILL.md`           |
| Write PHireScript source files            | `skills/write-phirescript/SKILL.md`          |
| Write CaseValidation.php assertions       | `skills/validate-compilation/SKILL.md`       |
| Understand the type system                | `skills/phirescript-types/SKILL.md`          |
| Debug a compilation failure               | `skills/debug-compiler/SKILL.md`             |
| Change source/dist paths in config        | `skills/configure-phirescript-json/SKILL.md` |
| Add or filter by tags                     | `skills/case-metadata/SKILL.md`              |

## Key Files

| File                        | Purpose                                          |
|-----------------------------|--------------------------------------------------|
| `bin/stretch`               | Orchestrator CLI entry point                     |
| `orchestrator/`             | Framework: Orchestrator, AbstractCaseValidation  |
| `samples/success/case_N/`   | Test cases (34 cases currently)                  |
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

## Read Next

- [brief.md](brief.md) — 10-line project summary
- [repo.md](repo.md) — Structure, tech stack, execution flow
- [glossary.md](glossary.md) — Key terms
