---
name: parser-context-resolver
description: The parser's core pattern — Context (scope limiter) + Resolver (token matcher) + Node (data holder) — and how to work with them
metadata:
  type: skill
---

# Skill: Parser — Context + Resolver + Node

## Triggers

- "how does the parser work", "context stack", "how to add to the parser"
- "ContextManager", "TokenManager", "ContextTokenResolver"
- "isTheCase / resolve", "AbstractContext", "ParseContext"
- "parser not recognizing token X", "construct not parsed"

## When to Use

Use when adding new syntax to the parser, debugging parse-phase issues, or understanding how any existing language construct is parsed.

## Repository Context

- Parser: `src/Compiler/Parser.php`
- Context base: `src/Compiler/Parser/Ast/Context/AbstractContext.php`
- Resolver interface: `src/Compiler/Parser/Ast/Resolver/ContextTokenResolver.php`
- Node base: `src/Compiler/Parser/Ast/Nodes/Node.php`
- Context directory: `src/Compiler/Parser/Ast/Context/`
- Node directory: `src/Compiler/Parser/Ast/Nodes/`
- Resolver directory: `src/Compiler/Parser/Ast/Resolver/`
- Managers: `src/Compiler/Parser/Managers/`

## Key Patterns

### The Trio

Every language construct is implemented as three cooperating classes:

```
Resolver           → "does this token start this construct?"
  isTheCase()      → bool
  resolve()        → create Node, create Context, enter Context

Context            → "manage this scope while inside it"
  handle()         → dispatch current token to own resolvers
  canClose()       → bool — "should we exit this scope now?"
  onClose()        → called once before exit (finalize node)
  afterClose()     → called once after exit (notify parent context)

Node               → plain data holder (no logic)
  $name, $body, $modifiers, ...
```

### Parser Main Loop

```php
// src/Compiler/Parser.php (simplified)
while ($tokenManager->hasTokens()) {
    $token = $tokenManager->current();
    $contextManager->handle($token, $parseContext);
    $tokenManager->advance();
}
```

`ContextManager::handle()` dispatches the token to the **active (top of stack) context**.
The active context iterates its registered resolvers, calls `isTheCase()` on each,
and calls `resolve()` on the first match.

### ContextManager Operations

```php
$contextManager->enter(AbstractContext $context)  // push onto stack
$contextManager->exit()                           // pop top of stack
$contextManager->exitUntil(string $contextClass)  // pop until matching type
$contextManager->isIn(string $contextClass): bool // check if context is active
$contextManager->current(): AbstractContext       // peek top of stack
```

### TokenManager Operations

```php
$tokenManager->current(): Token         // current token
$tokenManager->advance(): void          // move to next token
$tokenManager->peek(int $offset): Token // look ahead without advancing
$tokenManager->walk(int $steps): void   // advance N steps
$tokenManager->sequence(): SequenceBuilder  // start a multi-token pattern
```

### SequenceBuilder — Fluent Pattern Matching

```php
// Example: match "class ClassName {"
$tokenManager->sequence()
    ->once(fn(Token $t) => $t->isKeyword() && $t->value === 'class')
    ->then(fn(Token $t) => $t->isVariable())
    ->then(fn(Token $t) => $t->value === '{')
    ->matches();
```

Methods: `once()`, `then()`, `optional()`, `separated()`, `group()`, `or()`, `around()`, `lookAhead()`, `lookBehind()`.

### ParseContext — Shared State

Passed to every `Context::handle()` and `Resolver::resolve()`:

```php
class ParseContext {
    public TokenManager $tokenManager;
    public ContextManager $contextManager;
    public VariableManager $variableManager;
    public SymbolTableManager $symbolTableManager;
    public DependencyGraphBuilder $dependencyBuilder;
    public CompilerContext $compilerContext;
    public Program $program;
}
```

### Concrete Example: ClassResolver

```
Token stream: class, User, {, save, (, ), :, Bool, {, return, true, }, }

1. ProgramContext active
   → ClassResolver::isTheCase(token='class') → true
   → ClassResolver::resolve():
     - creates ClassNode
     - reads next token (User) → sets node->name = 'User'
     - creates ClassContext($classNode)
     - contextManager->enter(ClassContext)

2. ClassContext active — token '{'
   → ClassBodyResolver::isTheCase('{') → true
   → ClassBodyResolver::resolve():
     - creates ClassBodyNode
     - creates ClassBodyContext($classBodyNode)
     - contextManager->enter(ClassBodyContext)

3. ClassBodyContext active — token 'save'
   → MethodDeclarationResolver::isTheCase('save') → true (it's a variable-like name)
   → MethodDeclarationResolver::resolve():
     - creates MethodDeclarationNode, name='save'
     - creates MethodDeclarationContext
     - contextManager->enter(MethodDeclarationContext)

   [params, return type parsed inside MethodDeclarationContext...]

   → token '{'
   → MethodScopeResolver::isTheCase('{') → true
   → creates MethodScopeNode, MethodScopeContext
   → contextManager->enter(MethodScopeContext)

   [body statements parsed...]

4. MethodScopeContext::canClose('}') → true → contextManager->exit()
   ClassBodyContext::canClose('}') → true → contextManager->exit()
   ClassContext::canClose is managed by ClassBodyContext::afterClose() → notifies parent
   contextManager->exit() → back to ProgramContext
```

### AbstractContext API

```php
abstract class AbstractContext {
    abstract public function handle(Token $token, ParseContext $ctx): void;
    abstract public function canClose(Token $token): bool;
    public function onClose(ParseContext $ctx): void {}  // optional override
    public function afterClose(ParseContext $ctx): void {}  // optional override
    public function validation(ParseContext $ctx): void {}  // optional post-close validation
}
```

### Node Base

```php
abstract class Node {
    public Token $token;
    public int $line;
    public int $column;
    // Subclass adds typed public fields
}
```

Nodes are plain data bags — no methods. All logic lives in Contexts, Resolvers, Binders, Checkers, and Emitters.

## Critical Rules

1. **Resolvers only match, Contexts manage** — a Resolver should do the minimum needed to create a Node and Context; do not parse sub-constructs in `resolve()`.
2. **Never skip tokens in `handle()` without advancing TokenManager** — the parser loop calls `advance()` once per iteration; if a Resolver consumes extra tokens, it must call `$tokenManager->walk(N)` itself.
3. **`canClose()` must return `true` on the correct closing token** — incorrect close detection leaves the stack in a broken state for subsequent tokens.
4. **Nodes are immutable after parsing** — Binders can add metadata; don't re-mutate in Checkers or Emitters.
5. **Comments are `T_COMMENT` tokens** — contexts must handle or skip them explicitly; they appear inline in the token stream.

## Common Mistakes

- `isTheCase()` too broad → catches tokens that belong to sibling constructs
- Forgetting to `walk()` extra tokens consumed in `resolve()` → parser double-reads
- `canClose()` checks wrong token → context stays open, swallowing subsequent constructs
- Storing parse state on the Resolver instance → Resolvers may be instantiated once and reused

## Validation Checklist

- [ ] Resolver `isTheCase()` is narrow and unambiguous
- [ ] `resolve()` creates exactly one Node and enters exactly one Context
- [ ] Context `canClose()` fires on the correct closing delimiter
- [ ] Extra tokens consumed in `resolve()` are walked over with `$tokenManager->walk(N)`
- [ ] `onClose()` / `afterClose()` used to wire Node into parent Node's data
- [ ] `php bin/debug <file.ps>` shows the new construct in the AST dump

## Examples

See: [examples/](examples/)
