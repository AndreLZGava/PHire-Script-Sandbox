# Coding Standards — PHireScript Compiler

## PHP Standards

- **PHP version:** >=8.1 (uses readonly, enums, named arguments, match, fibers — not yet)
- **Target refactor version:** PHP 8.2 (Rector configured)
- **Code style:** PSR-12 enforced by PHP-CS-Fixer
- **Static analysis:** PHPStan level 9 — no exceptions without documented reason
- **Mess detection:** PHPMD (cyclomatic complexity, coupling, naming)

## Autoload

```
PHireScript\        → src/
PHireScript\Tests\  → tests/
```

## Naming Conventions

| Concept | Pattern | Example |
|---|---|---|
| Context classes | `*Context` | `ClassContext`, `MethodDeclarationContext` |
| Resolver classes | `*Resolver` | `ClassResolver`, `MethodDeclarationResolver` |
| Node classes | `*Node` | `ClassNode`, `MethodDeclarationNode` |
| Emitter classes | `*Emitter` | `ClassEmitter`, `MethodEmitter` |
| Binder classes | `*Binder` | `ClassBinder`, `PropertyBinder` |
| Checker classes | `*Checker` | `ClassChecker`, `MethodReturnChecker` |
| Methods descriptors | `*Methods` | `StringMethods`, `EmailMethods` |
| Test classes | `*Test` | `ParserTest`, `ClassEmitterTest` |

## Directory Structure

Every concept has a consistent home:

```
src/Compiler/Parser/Ast/Context/Declarations/   ← declaration contexts
src/Compiler/Parser/Ast/Context/Scopes/         ← scope contexts
src/Compiler/Parser/Ast/Nodes/Declarations/     ← declaration nodes
src/Compiler/Parser/Ast/Nodes/Statements/       ← statement nodes
src/Compiler/Parser/Ast/Resolver/Declaration/   ← declaration resolvers
src/Emitter/Declarations/                       ← declaration emitters
src/Emitter/OOP/                                ← OOP emitters
src/Binder/Declaration/                         ← declaration binders
src/Checker/Declaration/                        ← declaration checkers
```

## CompilerPass Ordering

| Order range | Phase |
|---|---|
| 10          | Type registration — must run first |
| 20          | Cross-type resolution |
| 30          | Member binding (reads registered types) |
| 40          | Modifier/metadata normalization |

## Commit Conventions

- No AI co-author in commit messages
- Follow conventional commits: `feat:`, `fix:`, `refactor:`, `test:`, `docs:`, `chore:`
- `phirescript/` is committed independently from the sandbox (separate git history)
- Quality gates must pass: `composer quality` before committing

## Exception Handling

- **Syntax errors** → throw `CompileException(message, line, column)` — in Scanner or Validator
- **Semantic errors** → throw `CheckerException(message, line, column)` — in Checker only
- **Fatal/internal** → wrapped by `FatalErrorException::prettyException()` — never throw from Binders
- No generic `\Exception` or `\RuntimeException` from compiler code — always use typed exceptions

## Emitter Contract

- Emitters are stateless — no mutable state on emitter instances
- Return valid PHP string; let nikic/php-parser normalize formatting
- Delegate child emission to `$ctx->emitter->emit($child, $ctx)`
- Register use statements via `$ctx->uses->add()`

## PassDiscovery Contract

- All Binder/Checker implementations in `src/Binder/` or `src/Checker/`
- All must have `#[CompilerPass(order: N)]` attribute
- PassDiscovery discovers them via reflection — no manual registration needed

## Quality Scripts

```bash
composer test       # PHPUnit
composer analyse    # PHPStan level 9 + PHPMD
composer format     # PHP-CS-Fixer PSR-12 (modifies files)
composer refactor   # Rector PHP 8.2 (modifies files)
composer quality    # all four in sequence
```
