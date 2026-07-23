# Tech Lead Done — PHireScript Compiler Knowledge Base

**Generated:** 2026-05-22
**Scope:** `phirescript/` (compiler repo)

## What Was Generated

### Overview files (5)
- `AGENTS.md` — agent entry point with orientation table and key files
- `brief.md` — 10-line summary of the compiler
- `repo.md` — full directory map, tech stack, pipeline overview, key commands
- `glossary.md` — 35+ terms covering every major compiler concept
- `index.md` — navigation index

### Skills (9)
| Skill | Coverage |
|---|---|
| `compilation-pipeline` | Full multi-phase pipeline: dependency graph → binding → checking → emission → post-processing |
| `add-language-feature` | End-to-end guide: Scanner + Parser + Binder + Checker + Emitter + sandbox test |
| `parser-context-resolver` | Context/Resolver/Node trio, ContextManager, TokenManager, SequenceBuilder |
| `write-emitter` | NodeEmitter interface, EmitterDispatcher, EmitContext, PhpTypeResolver, UseRegistry |
| `write-binder-checker` | Binder/Checker interfaces, CompilerPass ordering, PassDiscovery, SymbolTable API |
| `type-system` | Primitives, super types, meta types, PHP mapping, method descriptors, SymbolTableManager |
| `debug-compiler` | bin/debug, snapshot, phase identification, PHPStan, nikic error isolation |
| `run-tests` | PHPUnit, quality scripts, writing parser/emitter/integration tests |
| `scanner-tokens` | Token type reference, Token class API, adding keywords/super types, ModifiersTransform |

### Reference files (3)
- `reference/documentation.md` — external library links, sandbox relationship
- `reference/codeStands.md` — PHP/naming/pass-ordering/commit conventions
- `reference/dependency_graph.toon` — text dependency graph (tooling not available)

## Key Findings

1. **Context + Resolver + Node is the dominant parser pattern** — every language construct follows this exact trio. Understanding it unlocks the entire parser.

2. **PassDiscovery is implicit** — Binders and Checkers don't need to be registered anywhere. Just adding `#[CompilerPass(order: N)]` and placing the file in the right directory is sufficient. This is non-obvious and a common trap.

3. **Two-phase compilation** — all files are parsed+bound before any file is checked+emitted. This is required for cross-file type resolution and is easy to violate when adding a feature.

4. **Emitter output is pre-PHP** — nikic/php-parser transforms it; errors from nikic are NOT PHireScript errors. The `.phc` snapshot separates Emitter bugs from Processor bugs.

5. **464 source files** — the compiler is large. The `PassDiscovery` + auto-loading patterns are what make it extensible without manual registration.

6. **`SymbolTableManager` auto-loads `*Methods.php` via reflection** — adding a new type's method descriptor file to the right directory is all that's needed. No import required.

7. **Quality gates are strict** — PHPStan level 9, PSR-12, PHPMD, Rector PHP 8.2. All four are in `composer quality`. Any PR that skips them will fail CI.

8. **Magic methods in PHireScript map to `__magic` PHP counterparts** — this is implemented in `MagicMethods.php` and `MagicMethodDeclarationBinder`. The mapping is fixed; magic method names are recognized as `T_MAGIC_METHODS` tokens.

## Assumptions

- PHP 8.1+ runtime (confirmed by `composer.json` `require.php`)
- nikic/php-parser v5.x (confirmed by `composer.json`)
- PassDiscovery uses reflection to scan `src/Binder/` and `src/Checker/` directories (inferred from usage; specific scanning code was not read in full)
- `SymbolTableManager` auto-load scans `src/Runtime/DefaultOverrideMethods/` (inferred from description)

## Suggested Next Improvements

1. **Add example files to each skill** — especially `add-language-feature` (a minimal worked example would be very high value).

2. **Document Sketch vs Partial vs Functional feature status** — `architecture.md` has this table; it should be mirrored in the KB as a reference matrix.

3. **Document the `.pht` test file pipeline separately** — the `validate` mode / TEST mode has its own resolvers and contexts for `test`, `skip`, `validate`, `beforeAll` keywords; currently undocumented in the KB.

4. **Document the DI resolver system** — `resolver: laravel|symfony|custom` in PHireScript.json controls DI behavior; this is currently in "sketch" phase but would benefit from documenting the intended design.

5. **Document SequenceBuilder examples** — the fluent pattern-matching API is powerful but the skill only gives one example; more real-world patterns from the codebase would help.

6. **AccessorHandler documentation** — getter/setter syntax (`+>`, `<>`, `#>`) is handled in post-emission by `AccessorHandler`; this crosses both Parser and Processor layers and deserves its own skill.
