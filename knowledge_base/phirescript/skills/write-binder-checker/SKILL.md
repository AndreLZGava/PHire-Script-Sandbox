---
name: write-binder-checker
description: Implement Binder and Checker compiler passes — populate SymbolTable, attach metadata, validate semantics
metadata:
  type: skill
---

# Skill: Write Binder / Checker

## Triggers

- "add a Binder", "add a Checker", "CompilerPass", "semantic validation"
- "register type in SymbolTable", "attach metadata to AST node"
- "validate that X is required", "throw CheckerException"
- Compilation proceeds but produced PHP is wrong due to missing metadata
- A semantic rule is not enforced (e.g., wrong return type not caught)

## When to Use

Use when a new language feature needs:
- **Binder:** type registration, metadata attachment, member resolution, modifier normalization
- **Checker:** semantic validation rules (throw `CheckerException` with line + column)

## Repository Context

- Binder orchestrator: `src/Binder.php`
- Binder interface: `src/Binder/Binder.php`
- Checker orchestrator: `src/Checker.php`
- Checker abstract base: `src/Checker/Checker.php`
- Pass discovery: `src/PassDiscovery.php`
- CompilerPass attribute: `src/CompilerPass.php`
- SymbolTable: `src/SymbolTable.php`
- Existing binders: `src/Binder/Declaration/`, `src/Binder/Root/`
- Existing checkers: `src/Checker/Declaration/`, `src/Checker/Expression/`

## Key Patterns

### Binder interface

```php
interface Binder
{
    public function mustBind(object $node): bool;
    public function bind(object $node, Binder $dispatcher): void;
}
```

- `mustBind()` → return `true` when this binder handles the given node type
- `bind()` → perform binding; `$dispatcher` can be called to bind child nodes

### Minimal Binder

```php
namespace PHireScript\Binder\Declaration;

use PHireScript\Binder\Binder;
use PHireScript\CompilerPass;
use PHireScript\Compiler\Parser\Ast\Nodes\Declarations\MyConstructNode;

#[CompilerPass(order: 30)]
class MyConstructBinder implements Binder
{
    public function mustBind(object $node): bool
    {
        return $node instanceof MyConstructNode;
    }

    public function bind(object $node, Binder $dispatcher): void
    {
        // Example: register type
        // $this->symbolTable->registerTypeDefinition($node->name, $node);

        // Example: bind children
        foreach ($node->body->statements as $statement) {
            $dispatcher->bind($statement, $dispatcher);
        }

        // Example: attach resolved metadata to node
        $node->resolvedModifiers = $this->resolveModifiers($node->rawModifiers);
    }
}
```

### TypeRegistrationBinder pattern

For constructs that define a type (class, interface, trait), registration must happen in Phase A (early):

```php
#[CompilerPass(order: 10)]   // run early — before any binder that reads types
class TypeRegistrationBinder implements Binder
{
    public function mustBind(object $node): bool
    {
        return $node instanceof ClassNode
            || $node instanceof InterfaceNode
            || $node instanceof TraitNode
            || $node instanceof MyConstructNode; // ← add new types here
    }

    public function bind(object $node, Binder $dispatcher): void
    {
        $this->symbolTable->registerTypeDefinition($node->name, $node);
    }
}
```

### CompilerPass ordering

```php
#[CompilerPass(order: 10)]   // Phase A — type registration (runs first)
#[CompilerPass(order: 20)]   // Phase B — cross-type resolution
#[CompilerPass(order: 30)]   // Phase C — member binding (reads registered types)
#[CompilerPass(order: 40)]   // Phase D — modifier resolution
```

Lower order = runs earlier. `PassDiscovery` sorts by this value. When in doubt: put new Binders at `order: 30` (after type registration).

### Checker abstract base

```php
abstract class Checker
{
    abstract public function mustCheck(object $node): bool;
    abstract public function check(object $node, Checker $dispatcher): void;

    // Helper for conditional dispatch:
    protected function willCheck(array $nodes, Checker $dispatcher): void
    {
        foreach ($nodes as $node) {
            $dispatcher->check($node, $dispatcher);
        }
    }
}
```

### Minimal Checker

```php
namespace PHireScript\Checker\Declaration;

use PHireScript\Checker\Checker;
use PHireScript\CompilerPass;
use PHireScript\Runtime\Exceptions\CheckerException;

#[CompilerPass(order: 30)]
class MyConstructChecker extends Checker
{
    public function mustCheck(object $node): bool
    {
        return $node instanceof MyConstructNode;
    }

    public function check(object $node, Checker $dispatcher): void
    {
        if (empty($node->name)) {
            throw new CheckerException(
                message: 'MyConstruct must have a name',
                line: $node->line,
                column: $node->column ?? 0
            );
        }

        // Delegate child checking:
        if ($node->body) {
            $dispatcher->check($node->body, $dispatcher);
        }
    }
}
```

### CheckerException

```php
use PHireScript\Runtime\Exceptions\CheckerException;

throw new CheckerException(
    message: 'Human-readable error message',
    line: $node->line,
    column: $node->column
);
```

`FatalErrorException` catches this and renders it with file path, line, and column indicator.

### PassDiscovery auto-registration

Both Binders and Checkers are **auto-discovered** via reflection by `PassDiscovery`.
You do NOT need to register them anywhere — just:
1. Add `#[CompilerPass(order: N)]` attribute
2. Implement the interface/abstract class
3. Place the file in the correct namespace directory (`src/Binder/` or `src/Checker/`)

`PassDiscovery` scans these directories via reflection at startup.

### SymbolTable API

```php
$symbolTable->registerTypeDefinition(string $name, object $node): void;
$symbolTable->getTypeDefinition(string $name): ?object;
$symbolTable->enterScope(string $scopeName): void;
$symbolTable->exitScope(): void;
```

### Modifier resolution example

```php
// ModifiersBinder.php pattern:
public function bind(object $node, Binder $dispatcher): void
{
    $node->phpModifier = match($node->rawModifier) {
        '*'  => 'public',
        '+'  => 'protected',
        '#'  => 'private',
        default => 'public',
    };
}
```

## Critical Rules

1. **TypeRegistrationBinder must run first** — any Binder that reads type definitions must have `order > 10`.
2. **Checkers run after all Binders** — `PassDiscovery` processes all Binders first, then all Checkers. Never rely on SymbolTable state in a Checker that wasn't set by a Binder.
3. **`#[CompilerPass(order: N)]` is mandatory** — Binders/Checkers without this attribute are invisible to PassDiscovery.
4. **Throw `CheckerException`, not generic exceptions** — only `CheckerException` and `CompileException` produce human-readable error output with line/column.
5. **Binders must not throw** — exceptions in Binders become FatalErrorException with poor context. Validate in Checkers instead.
6. **Both `mustBind`/`mustCheck` and the implementation method must agree** — if `mustBind` returns true for `ClassNode`, `bind()` will receive a `ClassNode`.

## Common Mistakes

- Missing `#[CompilerPass(order: N)]` → PassDiscovery skips the class entirely (silent)
- Order too high in TypeRegistrationBinder → other Binders run before types are registered → `getTypeDefinition()` returns null
- Throwing in a Binder instead of Checker → error has no line context
- Binder in wrong namespace (`src/Checker/` instead of `src/Binder/`) → PassDiscovery misroutes it

## Validation Checklist

- [ ] `#[CompilerPass(order: N)]` present with correct order (10 for registration, 30+ for binding/checking)
- [ ] `mustBind()` / `mustCheck()` is narrow and type-safe
- [ ] File is in `src/Binder/` or `src/Checker/` directory (PassDiscovery scans these)
- [ ] Checker throws `CheckerException` with `line` and `column`
- [ ] Binder does not throw; validates in Checker instead
- [ ] `composer test` + `composer analyse` pass

## Examples

See: [examples/](examples/)
