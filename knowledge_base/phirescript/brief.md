# Brief — PHireScript Compiler

PHireScript is a **PHP transpiler**: it reads `.ps` source files and emits valid `.php` output.

- **Pipeline:** Scanner → Validator → Parser → Binder → Checker → Emitter → Processors → File write
- **Parser model:** context-based recursive descent — each construct has a `Context` + `Resolver` + `Node` trio
- **Binding:** two-pass — first registers all types in `SymbolTable`, then binds members; enables cross-file resolution
- **Emission:** `EmitterDispatcher` dispatches each AST node to the matching `NodeEmitter`; ~60 emitters total
- **Post-processing:** nikic/php-parser parses emitted string, visitors transform it, pretty-prints final PHP
- **Type system:** primitives (`String`, `Int`, …), super types (`Email`, `Uuid`, …), meta types (`Date`, `Currency`, …)
- **Method mappings:** each type has a `*Methods.php` descriptor class; `SymbolTableManager` auto-loads all via reflection
- **Quality gates:** PHPStan level 9, PSR-12 (CS-Fixer), PHPMD, Rector (PHP 8.2 target)
- **464 source files** across 10+ subsystems; 67 unit test files
- **Sandbox role:** `phirescript/` is git-ignored inside the sandbox — developed and committed here independently
