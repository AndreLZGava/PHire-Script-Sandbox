# Repository Overview — PHireScript Compiler

## Tech Stack

- **Language:** PHP 8.1+ (requires `>=8.1`)
- **Parser library:** `nikic/php-parser ^5.0` (post-emission transformation)
- **Tests:** PHPUnit ^12.5
- **Static analysis:** PHPStan level 9
- **Code style:** PHP-CS-Fixer (PSR-12), PHPMD
- **Refactoring:** Rector (PHP 8.2 target)
- **Autoload:** Composer PSR-4 — `PHireScript\` → `src/`

## Directory Map

```
phirescript/
├── bin/                          CLI entry points
│   ├── build                     Compile .phs → .php (BUILD mode)
│   ├── watch                     File watcher — hot reload (WATCH mode)
│   ├── debug                     Token/AST inspection for one file (DEBUG mode)
│   ├── snapshot                  Generate .phc intermediates (SNAPSHOT mode)
│   ├── validate                  Compile .pht test files (TEST mode)
│   ├── validateCompiled          php -l + PHPUnit on compiled output
│   └── init                      Interactive setup — create PHireScript.json
├── src/
│   ├── Compiler.php              Main orchestrator — multi-phase compile()
│   ├── Transpiler.php            Per-file pipeline
│   ├── TranspilerDependencyTree.php  Pre-build dependency pass
│   ├── SymbolTable.php           Global type registry, scoped via enterScope/exitScope
│   ├── DependencyGraphBuilder.php    Inter-file dependency DAG + topological sort
│   ├── PassDiscovery.php         Auto-discovers Binder/Checker via reflection
│   ├── CompilerPass.php          #[CompilerPass(order: N)] attribute
│   │
│   ├── Core/
│   │   ├── CompileMode.php       Enum: BUILD, TEST, DEBUG, SNAPSHOT, WATCH, CHECK
│   │   └── CompilerContext.php   Runtime context: mode, flags, paths
│   │
│   ├── Compiler/                 Lexer + Parser + AST
│   │   ├── Scanner.php           Regex tokenizer → Token[]
│   │   ├── Validator.php         Pre-parser validation + ModifiersTransform
│   │   ├── Parser.php            Recursive descent → Program AST
│   │   └── Parser/
│   │       ├── ParseContext.php  Runtime parse state passed to all contexts/resolvers
│   │       ├── Ast/
│   │       │   ├── Context/      Scope contexts (one per language construct)
│   │       │   ├── Nodes/        AST node data classes
│   │       │   └── Resolver/     Token pattern matchers → Node + Context creators
│   │       ├── Managers/
│   │       │   ├── ContextManager.php   Context stack
│   │       │   ├── TokenManager.php     Token cursor + peek/walk/sequence
│   │       │   ├── SymbolTableManager.php  Type-method registry (auto-loads Methods classes)
│   │       │   ├── VariableManager.php  Scope variable tracking
│   │       │   └── Builder/SequenceBuilder.php  Fluent token pattern matching
│   │       └── Transformers/ModifiersTransform.php  + → protected, # → private
│   │
│   ├── Binder.php + Binder/      Binding passes — populate SymbolTable
│   ├── Checker.php + Checker/    Semantic validation passes
│   ├── Emitter.php + Emitter/    Code generation — AST → PHP string
│   ├── Processors/               Post-emission nikic/php-parser transforms
│   ├── FileManager/              File I/O, watch loop, config loading, error rendering
│   ├── DependencyGraphBuilder/   Dependency graph node + tree parser
│   │
│   ├── Runtime/
│   │   ├── RuntimeClass.php      Constants: extensions, modifier maps, defaults
│   │   ├── Exceptions/           CompileException, CheckerException, FatalErrorException
│   │   ├── Types/                SuperTypes, MetaTypes base classes + implementations
│   │   ├── DefaultOverrideMethods/  Type method descriptors (*Methods.php per type)
│   │   └── CustomClasses/        Magic method → PHP __magic mappings
│   │
│   ├── Helper/
│   │   ├── Messenger.php         CLI/web output: success/error/warning/info (ANSI + HTML)
│   │   └── TypeResolver.php      Classifies type name: primitive/supertype/metatype/custom
│   │
│   ├── Visitor/                  nikic/php-parser AST visitors (post-emission pass)
│   └── Lexer/                    Low-level lexer primitives
│
├── tests/                        67 PHPUnit test files
│   ├── TranspilerTest.php        Main integration test
│   ├── Compiler/                 Unit tests per subsystem
│   └── Runtime/                  Runtime type tests
│
├── PHireScript.json              Compiler's own config (for dev/test compilation)
├── phpunit.xml                   PHPUnit config
├── phpstan.neon                  Static analysis (level 9)
├── phpmd.xml                     Mess detector
├── .php-cs-fixer.php             PSR-12 code style
├── rector.php                    PHP 8.2 upgrade target
└── architecture.md               In-depth architecture reference (read this!)
```

## Compilation Pipeline (Overview)

```
.phs file
  ↓ Scanner.tokenize()              Token[]
  ↓ Validator.validate()            Token[] (pre-parse check + modifier transform)
  ↓ Parser.parse()                  Program AST
  ↓ Binder.bind()                   Program AST (SymbolTable populated)
  ↓ Checker.check()                 Program AST (semantic validation)
  ↓ Emitter.emit()                  string (pre-PHP)
  ↓ PhpFileGeneratorHandler.process() string (formatted PHP via nikic)
  ↓ FileManager.persist()           .php file on disk
```

Across files: dependency graph → topological sort → above pipeline in correct order.

## Key Commands

```bash
# Compile all .phs files (reads PHireScript.json for paths)
php bin/build

# Compile specific dirs
php bin/build src/ps dist/php

# Hot reload
php bin/watch

# Inspect tokens + AST for one file
php bin/debug path/to/file.phs

# Generate .phc snapshots
php bin/snapshot

# Compile .pht test files
php bin/validate

# Run unit tests
composer test
# or
vendor/bin/phpunit

# Full quality check
composer quality

# Static analysis
composer analyse

# Code style fix
composer format

# Refactor (Rector)
composer refactor
```

## Committed State

- `phirescript/` has its own `composer.json` and git history — committed independently from the sandbox
- `PHireScript.json` here is the compiler's own dev config (for compiling its own Source/ samples)
- Quality gates (PHPStan level 9) must pass before committing
