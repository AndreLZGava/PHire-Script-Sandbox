# Knowledge Base Index — PHireScript Compiler

## Overview Documents

- [AGENTS.md](AGENTS.md) — Agent entry point: quick orientation table
- [brief.md](brief.md) — 10-line project summary
- [repo.md](repo.md) — Full directory map, tech stack, pipeline overview, key commands
- [glossary.md](glossary.md) — Compiler terminology (Token, Context, Resolver, Node, Emitter, etc.)

## Skills

| Skill                                                                          | When to use                                          |
|--------------------------------------------------------------------------------|------------------------------------------------------|
| [compilation-pipeline](skills/compilation-pipeline/SKILL.md)                   | Understanding the full .phs → .php flow               |
| [add-language-feature](skills/add-language-feature/SKILL.md)                   | Adding a new language construct end-to-end           |
| [parser-context-resolver](skills/parser-context-resolver/SKILL.md)             | Working inside the parser (Context + Resolver + Node)|
| [write-emitter](skills/write-emitter/SKILL.md)                                 | Writing a new NodeEmitter                            |
| [write-binder-checker](skills/write-binder-checker/SKILL.md)                   | Writing Binder or Checker passes                     |
| [type-system](skills/type-system/SKILL.md)                                     | Primitives, super types, meta types, method mappings |
| [debug-compiler](skills/debug-compiler/SKILL.md)                               | Diagnosing failures inside the compiler              |
| [run-tests](skills/run-tests/SKILL.md)                                         | Running and writing compiler unit tests              |
| [scanner-tokens](skills/scanner-tokens/SKILL.md)                               | Token types, lexer patterns, Token class             |

## Reference

- [reference/documentation.md](reference/documentation.md) — External docs and resource links
- [reference/codeStands.md](reference/codeStands.md) — Coding standards and quality gates
- [reference/dependency_graph.toon](reference/dependency_graph.toon) — Subsystem dependency graph

## Completion Report

- [tech_done/tech_lead_done.md](tech_done/tech_lead_done.md) — What was generated, findings, gaps
