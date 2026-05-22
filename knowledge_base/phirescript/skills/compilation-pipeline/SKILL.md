---
name: compilation-pipeline
description: The full multi-phase compilation pipeline from .ps source to .php output — dependency graph, binding, checking, emission, post-processing
metadata:
  type: skill
---

# Skill: Compilation Pipeline

## Triggers

- "how does compilation work", "what happens when I run bin/build"
- "pipeline overview", "how is .ps compiled to .php"
- "what order do files compile in", "dependency resolution"
- Debugging an issue that spans multiple phases

## When to Use

Use to understand where in the pipeline to look when a feature is broken, when adding a new compilation phase, or when diagnosing a pipeline-level failure.

## Repository Context

- Main orchestrator: `src/Compiler.php` — `compile(?string $sourceDir, ?string $distDir): void`
- Per-file pipeline: `src/Transpiler.php`
- Dependency graph: `src/DependencyGraphBuilder.php` + `src/TranspilerDependencyTree.php`
- Config loading: `src/FileManager/FileManager.php`
- Entry point: `bin/build` → `new CompilerContext(CompileMode::BUILD)` → `new Compiler($ctx)->compile()`

## Key Patterns

### Phase 0 — Config Loading

```php
// All bin/* scripts follow this pattern:
$context = new CompilerContext(
    mode: CompileMode::BUILD,
    inMemory: false,
    verbose: false,
    clean: false,
    displayInsideCompiler: false,
    file: '',
    targetWatch: ''
);
$compiler = new Compiler($context);
$compiler->compile($sourceDir, $distDir);
```

Config file `PHireScript.json` is read by `FileManager` to get `source`, `dist`, `namespace`, `resolver`, `dev`.

### Phase 1 — Dependency Graph

The compiler must know the correct **compilation order** across files to resolve cross-file types.

```
TranspilerDependencyTree::build()
  → light-parse each .ps file (pkg + use only, no full AST)
  → collect package declarations and use statements
  → DependencyGraphBuilder::buildGraph()
    → register package → file mappings
    → build edge list: "file A uses package B from file C"
    → topological sort → ordered list of files
  → cache the graph (invalidated when files change)
```

### Phase 2 — Binding Pass (all files, in topological order)

```
For each file (in topological order):
  → Transpiler::parseAndBind($code, $path)
    → Scanner::tokenize()       → Token[]
    → Validator::validate()     → Token[] (+ ModifiersTransform)
    → Parser::parse()           → Program AST
    → Binder::bind(Program)     → populate SymbolTable
```

After this pass, the shared `SymbolTable` contains type definitions for **all** files. This enables cross-file type resolution in subsequent phases.

### Phase 3 — Check + Emit (per file)

```
For each file (in topological order):
  → Transpiler::checkAndEmit($ast, $path, $distDir)
    → Checker::check(Program)             → semantic validation
    → Emitter::emit(Program)              → pre-PHP string
    → PhpFileGeneratorHandler::process()  → final PHP (via nikic)
    → FileManager::persist($php, $path)   → write .php
```

### Full per-file sequence

```
Source .ps
  ↓
Scanner::tokenize()
  → regex-based; produces Token[] with type, value, line, column
  ↓
Validator::validate()
  → pre-parse: rejects forbidden constructs
  → ModifiersTransform: + → protected, # → private, ~ → abstract
  ↓
Parser::parse()
  → context-based recursive descent
  → drives context stack via ContextManager
  → each construct: Resolver matches token → creates Node + Context
  → returns Program AST
  ↓
Binder::bind()
  → PassDiscovery auto-discovers Binder impls sorted by #[CompilerPass(order:)]
  → Phase A: TypeRegistrationBinder → register class/interface/trait names in SymbolTable
  → Phase B: ProgramBinder → walk all statements, dispatch to member binders
  → PropertyBinder, ClassBinder, MagicMethodDeclarationBinder, etc.
  ↓
Checker::check()
  → PassDiscovery auto-discovers Checker impls sorted by #[CompilerPass(order:)]
  → ClassChecker, ClassBodyChecker, MagicMethodsChecker, MethodReturnChecker, etc.
  → throws CheckerException on violation (line + column in exception)
  ↓
Emitter::emit()
  → EmitterDispatcher dispatches each AST node to matching NodeEmitter
  → ~60 emitters: ClassEmitter, MethodEmitter, IfStatementEmitter, etc.
  → produces pre-PHP string (still may need semicolons, type normalization, etc.)
  ↓
PhpFileGeneratorHandler::process()
  → nikic/php-parser: parse pre-PHP string → PHP AST
  → Visitor traversals: SemicolonHandler, ReturnTypeHandler, AccessorHandler,
    VariablesHandler, NativeTypesHandler, FunctionsHandler, ObjectsHandler
  → PrettyPrinter: PHP AST → final formatted .php string
  ↓
FileManager::persist()
  → write to dist/ directory
  → print: "✔ src/output/Foo.ps → src/compiled/Foo.php"
```

### Caching

- Dependency graph cached in `.cache/` — invalidated on file change or orphaned node
- AST cache — parsed `Program` objects cached to skip re-parsing unchanged files

### CompileModes

| Mode       | Behavior                                                      |
|------------|---------------------------------------------------------------|
| `BUILD`    | Full pipeline for all `.ps` files                             |
| `WATCH`    | File watcher loop; on change: re-run pipeline for changed file|
| `DEBUG`    | Runs Scanner + dumps tokens and AST for a single file         |
| `SNAPSHOT` | Stops after Emitter, writes `.psc` (pre-nikic string)         |
| `TEST`     | Full pipeline for `.pst` test files only                      |
| `CHECK`    | Parse + Bind + Check only, no emission                        |

## Critical Rules

1. **Binding before checking before emitting** — the SymbolTable must be fully populated (all files bound) before any file is checked or emitted.
2. **Topological order** — files that are `use`d by other files must compile before their dependents.
3. **PassDiscovery order matters** — `#[CompilerPass(order: N)]` determines which Binder/Checker runs first; lower N runs earlier.
4. **nikic/php-parser is post-emitter** — it operates on the string emitted by `Emitter::emit()`, not on the PHireScript AST.
5. **SymbolTable is shared across files** — write-then-read pattern; never read from SymbolTable before all binders have run.

## Common Mistakes

- Adding a new type and forgetting to register it in `TypeRegistrationBinder` → cross-file usage fails
- Adding a Binder that reads from SymbolTable in Phase A before Phase B has run → stale data
- Adding a Checker pass with wrong order → runs before required Binder, sees incomplete state
- Emitter returns a string that nikic can't parse → cryptic PhpParserException, not a PHireScript error

## Validation Checklist

- [ ] New feature has: Scanner tokens + Parser (Context+Resolver+Node) + Binder + Checker + Emitter
- [ ] Emitter output is valid pre-PHP that nikic can parse
- [ ] Any cross-file type reference is registered in SymbolTable via TypeRegistrationBinder
- [ ] `composer test` passes
- [ ] `composer analyse` (PHPStan level 9) passes

## Examples

See: [examples/](examples/)
