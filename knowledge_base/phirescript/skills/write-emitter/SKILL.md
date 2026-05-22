---
name: write-emitter
description: Implement a new NodeEmitter — the class that converts an AST node to a PHP code string
metadata:
  type: skill
---

# Skill: Write Emitter

## Triggers

- "add an emitter", "write a NodeEmitter", "emit new node to PHP"
- "how is X emitted", "emitter for MyConstructNode"
- "EmitterDispatcher", "EmitContext", "NodeEmitter interface"
- New feature is parsed and bound but produces empty output or wrong PHP

## When to Use

Use when a new AST node needs to generate PHP code. Every concrete `Node` subclass that can appear in a compiled program needs a corresponding `NodeEmitter`.

## Repository Context

- `NodeEmitter` interface: `src/Emitter/Base/NodeEmitter.php`
- `NodeEmitterAbstract`: `src/Emitter/Base/NodeEmitterAbstract.php`
- `EmitterDispatcher`: `src/Emitter/Base/EmitterDispatcher.php`
- `EmitContext`: `src/Emitter/Base/EmitContext.php`
- `PhpTypeResolver`: `src/Emitter/Base/Type/PhpTypeResolver.php`
- `UseRegistry`: `src/Emitter/Base/UseRegistry.php`
- Emitter orchestrator: `src/Emitter.php` (where dispatcher is built)
- Existing emitters for reference: `src/Emitter/Declarations/ClassEmitter.php`, `src/Emitter/OOP/MethodEmitter.php`

## Key Patterns

### NodeEmitter interface

```php
interface NodeEmitter
{
    public function supports(object $node, EmitContext $ctx): bool;
    public function emit(object $node, EmitContext $ctx): string;
}
```

- `supports()` → `true` when this emitter handles the given node
- `emit()` → returns the PHP code string for this node

### Minimal emitter

```php
namespace PHireScript\Emitter\Declarations;

use PHireScript\Compiler\Parser\Ast\Nodes\Declarations\MyConstructNode;
use PHireScript\Emitter\Base\EmitContext;
use PHireScript\Emitter\Base\NodeEmitter;

class MyConstructEmitter implements NodeEmitter
{
    public function supports(object $node, EmitContext $ctx): bool
    {
        return $node instanceof MyConstructNode;
    }

    public function emit(object $node, EmitContext $ctx): string
    {
        // Emit child nodes by delegating back to the dispatcher:
        $body = $ctx->emitter->emit($node->body, $ctx);
        return "// my construct: {$node->name}\n{$body}";
    }
}
```

### EmitContext API

```php
class EmitContext {
    public bool $dev;                  // dev mode flag from PHireScript.json
    public UseRegistry $uses;          // accumulates PHP use statements
    public EmitterDispatcher $emitter; // dispatcher — use to emit child nodes
    public bool $insideInterface;
    public bool $insideClass;
    public bool $insideMethod;
    public bool $insideTrait;
    // ... other flags
}
```

### Emitting child nodes

Always delegate to `$ctx->emitter->emit($childNode, $ctx)` — never recurse directly.
This ensures the dispatcher routes correctly to the right emitter for each child type.

```php
public function emit(object $node, EmitContext $ctx): string
{
    $parts = [];
    foreach ($node->body->statements as $statement) {
        $parts[] = $ctx->emitter->emit($statement, $ctx);
    }
    return implode("\n", $parts);
}
```

### Using PhpTypeResolver

Map PHireScript type names to PHP type syntax:

```php
use PHireScript\Emitter\Base\Type\PhpTypeResolver;

$phpType = PhpTypeResolver::resolve($node->returnType, $ctx);
// PHireScript String → string
// PHireScript Email → string
// PHireScript Uuid → string
// PHireScript UserModel → UserModel (custom type = class name)
// PHireScript String|Null → string|null (null always last in PHP 8)
```

### Registering PHP use statements

```php
// Add a use statement to the accumulated header block:
$ctx->uses->add('App\\Models\\User');
// UseRegistry::render() emits all collected use statements as a sorted block
```

### NodeEmitterAbstract helper

Extends `NodeEmitter`. Provides `removeEndPunctuation(string $name): string` for stripping `?` or `!` suffixes from method names.

### Registering the emitter

Open `src/Emitter.php` and add your emitter to the array where `EmitterDispatcher` is built:

```php
new EmitterDispatcher([
    // ... existing emitters ...
    new MyConstructEmitter(),
]);
```

### Context flags in supports()

Some emitters are context-dependent (e.g., same Node type emits differently inside interface vs class):

```php
public function supports(object $node, EmitContext $ctx): bool
{
    return $node instanceof MethodDeclarationNode && $ctx->insideInterface;
}
```

The dispatcher tries context-dependent emitters first via linear scan, falling back to the fast-path cache for unambiguous node types.

## Critical Rules

1. **Return a valid PHP string** — the emitter output is parsed by nikic/php-parser; invalid PHP causes a FatalErrorException from within the processor, not from the emitter itself.
2. **Never mutate the node** — emitters are read-only; all mutation happens in Binders.
3. **Delegate child nodes** — use `$ctx->emitter->emit($child, $ctx)` for children; never instantiate child emitters directly.
4. **Register the emitter** — if not registered in `Emitter.php`, the node is silently emitted as empty string.
5. **`supports()` must not have side effects** — it may be called multiple times during dispatcher warm-up.

## Common Mistakes

- Emitting raw PHireScript type names instead of using `PhpTypeResolver` → invalid PHP types
- Forgetting to register in `Emitter.php` → node produces no output, PHP file has missing constructs
- Returning PHP with wrong indentation → nikic re-indents, but logic errors are not fixed
- Direct string concatenation of PHP that requires a use statement but doesn't register it → `undefined class` at runtime

## Validation Checklist

- [ ] `NodeEmitter` interface implemented (both `supports()` and `emit()`)
- [ ] Child nodes emitted via `$ctx->emitter->emit($child, $ctx)`
- [ ] PHP types resolved via `PhpTypeResolver::resolve()`
- [ ] `use` statements accumulated via `$ctx->uses->add()`
- [ ] Emitter registered in `src/Emitter.php`
- [ ] Emitter output is valid PHP (test with `php -l` on the compiled file)
- [ ] Sandbox test case passes

## Examples

See: [examples/](examples/)
