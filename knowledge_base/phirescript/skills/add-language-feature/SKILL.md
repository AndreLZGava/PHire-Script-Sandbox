---
name: add-language-feature
description: End-to-end guide for adding a new language construct to PHireScript — Scanner → Resolver+Context+Node → Binder → Checker → Emitter
metadata:
  type: skill
---

# Skill: Add Language Feature

## Triggers

- "add a new keyword", "implement a new construct", "add support for X in PHireScript"
- "how do I add a feature to the compiler", "new syntax"
- User wants to compile a `.ps` construct that currently produces an error or is silently ignored

## When to Use

Use when implementing a new language construct from scratch. All phases must be touched: Scanner, Parser (Resolver+Context+Node), Binder, Checker, Emitter. Skipping any phase produces silent failures or cryptic errors.

## Repository Context

- Scanner: `src/Compiler/Scanner.php`
- Contexts: `src/Compiler/Parser/Ast/Context/`
- Nodes: `src/Compiler/Parser/Ast/Nodes/`
- Resolvers: `src/Compiler/Parser/Ast/Resolver/`
- Binders: `src/Binder/`
- Checkers: `src/Checker/`
- Emitters: `src/Emitter/`
- Method mappings: `src/Runtime/DefaultOverrideMethods/Types/`
- Reference: `architecture.md` — section "Adding New Language Features"

## Key Patterns

### Step 1 — Scanner: ensure the token exists

If the feature introduces a new keyword or symbol, add it to `src/Compiler/Scanner.php`:

```php
// Existing T_KEYWORD patterns — add your keyword to the list:
$keywords = ['class', 'interface', 'trait', 'type', 'extends', 'implements',
             'with', 'abstract', 'readonly', 'external', 'pkg', 'use',
             'if', 'elseif', 'else', 'return', 'try', 'handle', 'always',
             'YOUR_NEW_KEYWORD']; // ← add here

// Or add a new T_* token type with its own regex pattern
```

If the feature reuses existing tokens (e.g., a new context for an existing keyword), skip this step.

### Step 2 — Node: create the data holder

Add a new file under the appropriate `Nodes/` subdirectory:

```php
// src/Compiler/Parser/Ast/Nodes/Declarations/MyConstructNode.php
namespace PHireScript\Compiler\Parser\Ast\Nodes\Declarations;

use PHireScript\Compiler\Parser\Ast\Nodes\Node;

class MyConstructNode extends Node
{
    public string $name = '';
    public ?MyBodyNode $body = null;
    // Add only fields needed to carry parsed data
}
```

### Step 3 — Context + Resolver: parse the construct

#### Context

```php
// src/Compiler/Parser/Ast/Context/Declarations/MyConstructContext.php
namespace PHireScript\Compiler\Parser\Ast\Context\Declarations;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\ParseContext;

class MyConstructContext extends AbstractContext
{
    public function __construct(private MyConstructNode $node) {}

    public function handle(Token $token, ParseContext $ctx): void
    {
        // Dispatch to child resolvers for tokens inside this scope
        foreach ($this->resolvers() as $resolver) {
            if ($resolver->isTheCase($token, $ctx, $this)) {
                $resolver->resolve($token, $ctx, $this);
                return;
            }
        }
    }

    public function canClose(Token $token): bool
    {
        return $token->value === '}';  // adjust to correct closing token
    }

    public function onClose(ParseContext $ctx): void
    {
        // Wire body node into this node, notify parent context, etc.
    }

    private function resolvers(): array
    {
        return [
            new NameResolver(),
            new MyBodyResolver(),
        ];
    }
}
```

#### Resolver

```php
// src/Compiler/Parser/Ast/Resolver/Declaration/MyConstructResolver.php
namespace PHireScript\Compiler\Parser\Ast\Resolver\Declaration;

use PHireScript\Compiler\Parser\Ast\Resolver\ContextTokenResolver;

class MyConstructResolver implements ContextTokenResolver
{
    public function isTheCase(Token $token, ParseContext $ctx, AbstractContext $context): bool
    {
        return $token->isKeyword() && $token->value === 'myconstruct';
    }

    public function resolve(Token $token, ParseContext $ctx, AbstractContext $context): void
    {
        $node = new MyConstructNode();
        $node->token = $token;
        $node->line = $token->line;
        $ctx->contextManager->enter(new MyConstructContext($node));
    }
}
```

#### Register resolver in the parent context

Add `new MyConstructResolver()` to the resolver list of the context where this construct can appear (e.g., `ProgramContext` for top-level declarations, `ClassBodyContext` for class members).

### Step 4 — Binder: register types and attach metadata

If the feature introduces a type or needs metadata, add a Binder:

```php
// src/Binder/Declaration/MyConstructBinder.php
#[CompilerPass(order: 30)]
class MyConstructBinder implements Binder
{
    public function mustBind(object $node): bool
    {
        return $node instanceof MyConstructNode;
    }

    public function bind(object $node, Binder $dispatcher): void
    {
        // Register in SymbolTable if it's a type:
        // $this->symbolTable->registerTypeDefinition($node->name, $node);
        // Or attach metadata to existing nodes
    }
}
```

At minimum, `TypeRegistrationBinder` must handle `MyConstructNode` if it defines a type.

### Step 5 — Checker: validate semantics

```php
// src/Checker/Declaration/MyConstructChecker.php
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
                'MyConstruct requires a name',
                $node->line,
                $node->column
            );
        }
    }
}
```

### Step 6 — Emitter: generate PHP code

```php
// src/Emitter/Declarations/MyConstructEmitter.php
class MyConstructEmitter implements NodeEmitter
{
    public function supports(object $node, EmitContext $ctx): bool
    {
        return $node instanceof MyConstructNode;
    }

    public function emit(object $node, EmitContext $ctx): string
    {
        $body = $ctx->emitter->emit($node->body, $ctx);
        return "/* my construct */ {$node->name} { {$body} }";
    }
}
```

Register in `src/Emitter.php` (where `EmitterDispatcher` is built with all emitters).

### Step 7 — Add a sandbox test case

Create `samples/success/case_N/` with a `.ps` file using the new construct, add `CaseValidation.php`, and run:

```bash
php bin/stretch --mode=success --tags=your-new-tag
```

### Step 8 — Quality gates

```bash
composer test       # unit tests must pass
composer analyse    # PHPStan level 9 must pass
composer format     # PSR-12 style
```

## Critical Rules

1. **All 5 phases are required** — Scanner + Parser + Binder + Checker + Emitter. Partial implementations compile silently but produce wrong PHP.
2. **Binder ordering** — `TypeRegistrationBinder` (order: 10) must run before any Binder that reads type definitions. Your new Binder at order 30+ reads safely.
3. **Checker runs after all Binders** — the checker may assume the SymbolTable is fully populated.
4. **Emitter output must be valid pre-PHP** — nikic/php-parser will parse it; PHP syntax errors in emitter output surface as cryptic internal errors.
5. **Register the Emitter** — `EmitterDispatcher` must include your new `NodeEmitter` or the node is silently skipped during emission.

## Common Mistakes

- Adding a Resolver but forgetting to register it in the parent Context → new syntax silently ignored
- Creating a Binder/Checker without `#[CompilerPass(order: N)]` → PassDiscovery skips it
- Emitter returns invalid PHP → nikic parse error surfaced as FatalErrorException
- Forgetting the sandbox test case → no regression protection

## Validation Checklist

- [ ] Token exists in Scanner (or existing token is reused)
- [ ] `MyConstructNode` created with all needed fields
- [ ] `MyConstructContext` + `MyConstructResolver` created
- [ ] Resolver registered in parent Context's resolver list
- [ ] Binder handles node (registers type or attaches metadata)
- [ ] Checker validates required invariants
- [ ] Emitter is implemented and registered in EmitterDispatcher
- [ ] Sandbox test case in `samples/success/case_N/` passes
- [ ] `composer test` + `composer analyse` pass

## Examples

See: [examples/](examples/)
