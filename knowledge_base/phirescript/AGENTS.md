# PHireScript Compiler — Agent Entry Point

> **Location note:** This knowledge base lives inside the **sandbox** repo at
> `knowledge_base/phirescript/` — not inside the compiler repo itself.
> All file paths below are relative to the **compiler root** at `phirescript/`
> (i.e., `phirescript/src/Compiler.php`, not `src/Compiler.php`).
> To work on the compiler, open files under `phirescript/` in the sandbox tree.

This is the knowledge base for the **PHireScript compiler** (`phirescript/`).
PHireScript is a PHP transpiler: `.phs` source files → `.php` output via a multi-phase pipeline.

## Quick Orientation

| I want to…                                    | Go to                                                   |
|-----------------------------------------------|---------------------------------------------------------|
| Understand the full compilation pipeline      | `skills/compilation-pipeline/SKILL.md`                  |
| Add a new language construct                  | `skills/add-language-feature/SKILL.md`                  |
| Understand Context + Resolver + Node (parser) | `skills/parser-context-resolver/SKILL.md`               |
| Write a new NodeEmitter                       | `skills/write-emitter/SKILL.md`                         |
| Write a Binder or Checker pass                | `skills/write-binder-checker/SKILL.md`                  |
| Understand the type system                    | `skills/type-system/SKILL.md`                           |
| Debug a compilation failure in the compiler   | `skills/debug-compiler/SKILL.md`                        |
| Run the compiler's unit tests                 | `skills/run-tests/SKILL.md`                             |
| Understand tokens and the scanner             | `skills/scanner-tokens/SKILL.md`                        |

## Key Files

| File/Directory                     | Purpose                                                            |
|------------------------------------|--------------------------------------------------------------------|
| `src/Compiler.php`                 | Main orchestrator — multi-phase compile()                          |
| `src/Transpiler.php`               | Per-file pipeline: parse → bind → check → emit                    |
| `src/Compiler/Scanner.php`         | Lexer — regex tokenizer producing Token[]                          |
| `src/Compiler/Parser.php`          | Recursive descent parser → Program AST                             |
| `src/Compiler/Parser/Ast/Context/` | Parse contexts (scope limiters)                                    |
| `src/Compiler/Parser/Ast/Nodes/`   | AST node data classes                                              |
| `src/Compiler/Parser/Ast/Resolver/`| Token pattern matchers (Context + Node creators)                   |
| `src/Binder.php` + `src/Binder/`   | Binding passes — populate SymbolTable, attach metadata             |
| `src/Checker.php` + `src/Checker/` | Semantic validation passes                                         |
| `src/Emitter.php` + `src/Emitter/` | Code generation — AST node → PHP string                            |
| `src/Processors/`                  | Post-emission: nikic/php-parser transforms                         |
| `src/Runtime/Types/`               | SuperTypes, MetaTypes runtime base classes                         |
| `src/Runtime/DefaultOverrideMethods/` | Type method mappings (String, Int, Email, etc.)                 |
| `src/Helper/Messenger.php`         | CLI/web output with ANSI colors                                    |
| `src/PassDiscovery.php`            | Auto-discovers Binder/Checker impls via reflection + CompilerPass  |

## Sandbox Relationship

The compiler (`phirescript/`) is tested by the **PHire-Script-Sandbox** (this repo).
The sandbox's `knowledge_base/` (sibling of this folder) documents how to write test cases,
run the orchestrator, and interpret compilation output from the *consumer* perspective.
This folder documents the compiler's *internals*.

- Sandbox KB root: [`../AGENTS.md`](../AGENTS.md)
- Consumer skills (`.phs` syntax, CaseValidation, orchestrator): [`../skills/`](../skills/)

## Read Next

- [brief.md](brief.md) — 10-line summary
- [repo.md](repo.md) — full directory map and tech stack
- [glossary.md](glossary.md) — compiler terminology
