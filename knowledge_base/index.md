# Knowledge Base Index — PHire-Script-Sandbox

## Overview Documents

- [AGENTS.md](AGENTS.md) — Entry point for AI agents: quick orientation table
- [brief.md](brief.md) — 10-line project summary
- [repo.md](repo.md) — Directory map, tech stack, execution flow, key commands
- [glossary.md](glossary.md) — Terminology (`.phs`, `pkg`, `type`, `assertHasMessage`, etc.)

## Skills

| Skill                                                                                                    | When to use                                                       |
|----------------------------------------------------------------------------------------------------------|-------------------------------------------------------------------|
| [implement-phirescript-feature](skills/implement-phirescript-feature/SKILL.md)                          | Designing or implementing any PHireScript language feature        |
| [add-test-case](skills/add-test-case/SKILL.md)                                                           | Creating a new case_N in success/warning/error                    |
| [run-orchestrator](skills/run-orchestrator/SKILL.md)           | Running `bin/stretch` with modes and tag filters         |
| [write-phirescript](skills/write-phirescript/SKILL.md)         | Writing `.phs` source files                               |
| [validate-compilation](skills/validate-compilation/SKILL.md)   | Writing/fixing `CaseValidation.php` assertion files      |
| [phirescript-types](skills/phirescript-types/SKILL.md)         | Understanding the type system: primitives, super types   |
| [debug-compiler](skills/debug-compiler/SKILL.md)               | Diagnosing compilation failures                          |
| [configure-phirescript-json](skills/configure-phirescript-json/SKILL.md) | Setting source/dist paths for development    |
| [case-metadata](skills/case-metadata/SKILL.md)                 | Adding/using Tag, Description, Documentation attributes  |

## Reference

- [reference/documentation.md](reference/documentation.md) — External docs and links
- [reference/codeStands.md](reference/codeStands.md) — Coding standards and conventions
- [reference/dependency_graph.toon](reference/dependency_graph.toon) — Dependency graph placeholder

## PHireScript Compiler Knowledge Base

The compiler's KB lives here (not inside `phirescript/`) to avoid polluting the compiler repo.
All paths inside it are relative to the compiler root `phirescript/`.

- [phirescript/AGENTS.md](phirescript/AGENTS.md) — Compiler KB entry point
- [phirescript/index.md](phirescript/index.md) — Compiler KB index (9 skills)
- [phirescript/brief.md](phirescript/brief.md) — Compiler summary
- [phirescript/repo.md](phirescript/repo.md) — Compiler structure and pipeline

## Completion Reports

- [tech_done/tech_lead_done.md](tech_done/tech_lead_done.md) — Sandbox KB generation report
- [phirescript/tech_done/tech_lead_done.md](phirescript/tech_done/tech_lead_done.md) — Compiler KB generation report
