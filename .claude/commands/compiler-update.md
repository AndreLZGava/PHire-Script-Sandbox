# PHireScript Compiler — Code Update Guide

> Use this skill whenever modifying the PHireScript compiler: adding a new language feature, refactoring a compiler phase, adding or updating unit tests, or changing the pipeline architecture.

> **Auto-execution policy:** When this skill is active, run the following without asking for permission:
> - Any `php` command (compiler, bin/build, bin/debug, bin/stretch, bin/watch, bin/snapshot)
> - Any `composer` command (quality, test, format, refactor, analyse)
> - Any `vendor/bin/*` command (phpunit, phpstan, rector, php-cs-fixer)
> - `php bin/stretch` and all its flags
> - `ls`, `find`, `grep`, `cat` for file exploration
> - Read / Write / Edit on any file inside this repo or phirescript/
> - `git log`, `git diff`, `git status` (read-only git)

> **Self-update protocol:** After every code change to the compiler (feature, refactor, fix, test), update this file with:
> - Any new pattern, node type, resolver variant, or context trick discovered
> - Any correction to an existing section that turned out to be wrong or incomplete
> - A one-line entry at the bottom of section 12 (Changelog) describing what was added
> Keep the update minimal and surgical — only add what a future implementer would not otherwise know.

---

## 0. Understand the pipeline first

```
.ps source
  → Scanner       tokenizes into Token objects (regex-based, in order)
  → Validator     rejects forbidden keywords, checks bracket balance
  → Parser        drives a context stack; each token is handled by the active context
  → Binder        resolves symbols (skip for pure syntax features)
  → Checker       semantic validation (skip for pure syntax features)
  → Emitter       dispatches AST nodes to NodeEmitter classes
  → PhpFileGenerator (nikic/php-parser)
  → .php output
```

The Parser is the most complex phase. Key mental model:
- There is a **context stack** (`ContextManager`). The top of the stack is the *active* context.
- The main loop: `while (!end) { activeContext.handle(currentToken); advance(); }`
- A context can `enter` a child context (push) or signal that it `canClose`, which pops it and calls `afterClose`.

The **Sandbox** (`PHire-Script-Sandbox/`) is the integration harness that drives PHireScript. It owns:
- Sample `.ps` files and their expected compiled `.php` output
- `CaseValidation.php` per case asserting compiler messages
- `*Test.php` per case asserting runtime behavior of compiled PHP
- The orchestrator (`php bin/stretch`) that runs all cases end-to-end

Implement features inside `phirescript/`, validate them from the sandbox.

---

## 1. Scanner — recognize the token

File: `phirescript/src/Compiler/Scanner.php`

- Add new keywords to the `T_KEYWORD` regex. Order matters: longer patterns must come first (longest match wins):
  ```php
  'T_KEYWORD' => '/^\b(elseif|else|if|...)\b/'
  ```
- Add new multi-char operators to `T_MODIFIER`. Always put longer alternatives before shorter ones:
  ```php
  'T_MODIFIER' => '/^(\->|=>|===|!==|==|!=|<=|>=|&&|\|\|)/'
  ```
- `T_SYMBOL` is for single-char punctuation (`{`, `}`, `(`, `)`, `<`, `>`).
- **Never change tokenization to fix a parsing ambiguity** — disambiguation belongs in context/resolver logic.

---

## 2. Validator — guard against misuse (if needed)

Directory: `phirescript/src/Compiler/Validator/`

The Validator is structured like the Checker and Binder: a main `Validator.php` holds a `public ValidatorRule[] $rules` list and dispatches to each rule on every token.

**Interface** (`src/Compiler/Validator/ValidatorRule.php`):
```php
interface ValidatorRule {
    public function handleToken(Token $token, CompilerValidator $validator): void;
    public function afterTokens(CompilerValidator $validator): void;
}
```

**Rule folder structure:**

| Folder | Rule | Concern |
|---|---|---|
| `Tokens/` | `ForbiddenTokenRule` | Forbidden token map; throws on match |
| `Structure/` | `ObjectCountRule` | Max one class/interface/trait/type/immutable/validate per file; sets `$validator->mustHavePkg` |
| `Structure/` | `PackageRule` | Ensures `pkg` appears exactly once; enforces presence when required |
| `Structure/` | `BracketBalanceRule` | Counts `()`, `{}`, `[]`, `<>` pairs; `<>` only outside parens |

**Main `Validator.php`** exposes one shared state field accessed by rules:
- `public bool $mustHavePkg = false` — written by `ObjectCountRule`, read by `PackageRule::afterTokens`

**Rule ordering matters for `afterTokens`:** `BracketBalanceRule` is registered before `PackageRule` so bracket validation runs first (matching original behavior).

**Adding a new validation:**
1. Create `src/Compiler/Validator/<Folder>/FooRule.php` implementing `ValidatorRule`
2. Add it to `$this->rules` in `src/Compiler/Validator.php`

**`<`/`>` balance check must only run outside parentheses** — `BracketBalanceRule` tracks `$parenDepth` internally and guards:
```php
if ($this->parenDepth === 0) {
    $this->count($value, '<', '>');
}
```

**PHPStan note:** `Token::$value` is `readonly mixed`. Casting `(string) $token->value` triggers `cast.string` at level 9. Baseline these errors in `phpstan.baseline.neon` — the type cannot be changed and values are always strings at runtime.

---

## 3. AST Nodes — one node per construct

Directory: `phirescript/src/Compiler/Parser/Ast/Nodes/`

**Naming convention:**
- Scope/body nodes → `Scopes/FooScopeNode.php`
- Statement nodes → `Statements/FooNode.php`
- Expression nodes → `Expressions/FooNode.php`

**Pattern — scope node** (mirrors `IfScopeNode`, `ElseScopeNode`, `ElseIfScopeNode`):
```php
class FooScopeNode extends Node {
    public function __construct(public Token $token, public array $children = []) {}
}
```

**Pattern — statement node** (mirrors `IfNode`, `ElseIfNode`):
```php
class FooNode extends Statement {
    public function __construct(
        public Token $token,
        public ?ConditionNode $condition = null,
        public ?FooScopeNode $statements = null,
    ) {}
}
```

**Rules:**
- `Node` is the base for all AST nodes. `Statement` extends `Node` for top-level statements.
- Keep nodes as plain data holders — zero logic.
- Use nullable typed properties with defaults so partial construction is valid during parsing.
- If a parent node holds multiple child clauses of the same type (e.g., `elseif` blocks), use `public array $elseIfClauses = []` on the parent node — **not** a separate array node.
- When adding a new property to an existing node with positional constructor args, update all existing unit tests that instantiate that node — positional args shift.

---

## 4. Contexts — the parsing state machine

Directory: `phirescript/src/Compiler/Parser/Ast/Context/`

Each context wraps one node and manages a list of resolvers.

**Pattern — statement context** (mirrors `IfContext`, `ElseIfContext`):
```php
/** @extends AbstractContext<FooNode> */
class FooContext extends AbstractContext {
    private array $resolvers = [];

    public function __construct(FooNode $node) {
        parent::__construct($node);
        $this->resolvers = [
            new EndOfLineResolver(),                          // int key → early return
            'condition' => new OpeningFooConditionResolver(), // string key → sets node->condition
            'statements' => new FooScopeResolver(),           // string key → sets node->statements
        ];
    }

    public function handle(Token $token, ParseContext $parseContext): ?Node {
        foreach ($this->resolvers as $key => $resolver) {
            if ($resolver->isTheCase($token, $parseContext, $this)) {
                $token->processedBy = $resolver::class;
                $resolver->resolve($token, $parseContext, $this);
                $this->processResolvers($key);
                return null;
            }
        }
        throw new CompileException("$token->value not supported in foo context!", $token->line, $token->column);
    }

    private function processResolvers(int|string $key): void {
        if (\is_int($key)) return;
        $this->node->{$this->sanitizeKeys($key)} = $this->getChildrenValues($key) ?: [];
        $this->children = [];
    }

    public function canClose(Token $token, ParseContext $parseContext): bool {
        return false; // statement contexts never self-close
    }
}
```

**Key: resolver keys and `processResolvers`:**
- **Int key** (default when no key given in array literal) → `processResolvers` returns immediately. The resolver handles assignment itself (direct mutation or appending to parent node).
- **String key like `'condition'`** → after resolve, `processResolvers` assigns `$this->node->condition = $this->children[0]` and clears children.
- **`getChildrenValues($key)`** returns `current($this->children)` (first child) for all named keys — it does NOT support returning all children via a `[]` suffix. Use direct mutation for array properties.

**Pattern — scope context** (`afterClose` is critical):
```php
/** @extends AbstractContext<FooScopeNode> */
class FooScopeContext extends AbstractContext {
    // body resolvers: same set as ElseScopeContext

    public function handle(Token $token, ParseContext $parseContext): ?Node {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->isTheCase($token, $parseContext, $this)) {
                $resolver->resolve($token, $parseContext, $this);
                $this->node->children = $this->children; // sync node after each token
                return null;
            }
        }
        throw new CompileException(...);
    }

    public function canClose(Token $token, ParseContext $parseContext): bool {
        return $token->isClosingCurlyBracket();
    }

    public function afterClose(Token $token, ParseContext $parseContext): void {
        if ($token->isClosingCurlyBracket()) {
            $parseContext->contextManager->exit(); // exit intermediate context (e.g., FooContext)
            $next = $parseContext->tokenManager->getNextTokenAfterCurrent();
            if ($next->value !== 'else' && $next->value !== 'elseif') {
                $parseContext->contextManager->exit(); // exit parent (e.g., IfContext)
            }
        }
    }
}
```

**`afterClose` mental model:**
When `canClose()` returns true, the context manager automatically pops the current context (e.g., `FooScopeContext`). **Then** `afterClose` is called — at that point the active context is the **parent**. Each additional `exit()` call inside `afterClose` pops one more level. Count exits carefully: one per level you want to collapse at close time.

**The `walk(-1)` pattern for expression contexts:**
When an expression context closes on a non-value token (e.g., `)` or `\n`), call `parseContext->tokenManager->walk(-1)` in `afterClose` so the main loop's `advance()` keeps position on that token, letting the parent re-process it:
```php
public function afterClose(Token $token, ParseContext $parseContext): void {
    $parseContext->contextManager->current()->addChild($this->node);
    if (!in_array($token->type, self::VALUE_TYPES, true)) {
        $parseContext->tokenManager->walk(-1);
    }
}
```

---

## 5. Resolvers — the decision/dispatch layer

Directory: `phirescript/src/Compiler/Parser/Ast/Resolver/`

Each resolver implements `ContextTokenResolver`:
```php
interface ContextTokenResolver {
    public function isTheCase(Token $token, ParseContext $parseContext, AbstractContext $context): bool;
    public function resolve(Token $token, ParseContext $parseContext, AbstractContext $context): void;
}
```

**Pattern — scope opener resolver** (e.g., `ElseIfScopeResolver`, `IfScopeResolver`):
```php
class FooScopeResolver implements ContextTokenResolver {
    public function isTheCase(...): bool { return $token->isOpeningCurlyBracket(); }
    public function resolve(...): void {
        $node = new FooScopeNode(token: $token);
        $parseContext->contextManager->enter(new FooScopeContext($node));
        $context->addChild($node);
    }
}
```

**Pattern — keyword resolver that appends to a parent node array** (e.g., `ElseIfResolver`):
```php
class ElseIfResolver implements ContextTokenResolver {
    public function isTheCase(...): bool { return $token->value === 'elseif'; }
    public function resolve(...): void {
        $node = new ElseIfNode(token: $token);
        /** @var IfNode $ifNode */
        $ifNode = $context->node;
        $ifNode->elseIfClauses[] = $node;  // mutate parent directly
        $parseContext->contextManager->enter(new ElseIfContext($node));
        // no addChild — parent already holds the reference via elseIfClauses
    }
}
```

**Critical rule: resolvers must never call `$parseContext->tokenManager->advance()`.**
Only the Parser's main loop advances token position. Resolvers read state; the Parser drives forward.

**Context disambiguation for ambiguous tokens (`<`, `>`):**
If a token is valid in two contexts with different meanings (e.g., `<`/`>` as getter/setter vs. comparison operator), do NOT disambiguate in the scanner. Instead, add `ComparisonExpressionResolver` only to expression-level contexts (method body, if scope, else scope, try/handle/always, assignment, return) and keep it absent from class-body contexts where getter/setter syntax applies.

---

## 6. Binder — symbol binding for new declarations

Directory: `phirescript/src/Compiler/Binder/`

Skip this step for features that don't introduce new named declarations, new type categories, or new property annotations on class body members.

**Interface** (`src/Compiler/Binder/Binder.php`):
```php
interface Binder {
    public function mustBind(Node $node): bool;
    public function bind(Node $node, AstBinder $binder): void;
}
```

**The cascade pattern:** `ProgramBinder` iterates all `$binder->binders` for each top-level statement. `ClassBinder`/`InterfaceBinder` iterate all `$binder->binders` for each class/interface body child. Each binder is queried via `mustBind(Node)` — return `true` for the node type(s) it owns.

**Folder structure** mirrors the AST hierarchy:

| Folder | Handles |
|---|---|
| `Root/` | Program-level nodes (`TypeRegistrationBinder`, `ClassBodyBinder`) |
| `Declaration/` | `ClassNode`, `InterfaceNode`, `PropertyNode` |
| `Declaration/Class/` | `MethodDeclarationNode` inside a class |
| `Declaration/Interface/` | `InterfaceMethodDeclarationNode` |
| `Signatures/` | Modifier resolution |

**Creating a new binder:**
1. Create `src/Compiler/Binder/<Folder>/FooBinder.php` implementing `Binder`
2. Add it to `$this->binders` in `src/Compiler/Binder.php`

**Ordering rule — critical:** `TypeRegistrationBinder` is at index 0 and runs in the main `bind()` loop before `ProgramBinder` cascades. This guarantees all types are in `SymbolTable` before any body-level binder runs. If your binder reads from `SymbolTable`, place it after `ProgramBinder` in the array.

**Accessing `SymbolTable` and `Program` from a sub-binder:**
The main `Binder` class exposes two public fields for this purpose:
- `public readonly SymbolTable $globalTable` — type lookups and registration
- `public Program $program` — scan `UseNode` imports for alias resolution

Access them inside `bind(Node $node, AstBinder $binder)` via `$binder->globalTable` and `$binder->program`.

**PHPStan:** Sub-binders that access `$node->someProperty` (not declared on `Node` base class) need baseline entries. Follow the pattern of existing binder entries in `phpstan.baseline.neon` — each untyped property access gets one entry with `property.notFound` identifier. Update the baseline **manually** (surgical edits) rather than regenerating it — see Section 12.

---

## 7. Checker — semantic validation

File: `phirescript/src/Compiler/Checker.php`

Skip this step for features that don't need semantic rules beyond what the parser already enforces.

**`$this->checkers` list:** analogous to `$this->binders`. Register a new checker class here for new validation concerns.

**The two built-in validation entry points:**
- `checkClassBody($classNode)` — iterates body members; validates property constraints such as `readonly` + `defaultValue` conflict, and `abstract` property in a non-abstract class.
- `ensureReturnsForMethods(MethodDeclarationNode $method)` — validates return-type constraints imposed by method naming conventions (`mustBeBool`, `mustBeVoid`).

**Throwing validation errors:**
```php
throw new CheckerException("message", $node->line, $node->column);
```

**PHP 8.3 caution — `CheckerException`:** Do NOT use `readonly` on `$line` and `$column` in this class. PHP 8.3's `Exception::__construct` writes `$this->line` (backtrace line) after the promoted property is set, causing a fatal "modification of readonly property" error. Both properties are declared as plain `public` (no `readonly`).

---

## 8. Comparison / Binary expressions (Parser)

If the feature involves comparison or logical operators, use the existing infrastructure:

- `ComparisonExpressionResolver` fires on `>`, `<`, `==`, `===`, `!=`, `!==`, `>=`, `<=` when a left operand is available (via `peekPrevious()` or `context->children`).
- It pops the left node and enters `BinaryExpressionContext` which resolves the right side.
- Logical operators (`&&`, `||`) are handled inside `BinaryExpressionContext.handle()` — they chain the current node as the left of a new outer `BinaryExpressionNode`.
- Add `ComparisonExpressionResolver` to all scope/expression contexts that should support operators. Keep it off class-body contexts.

---

## 9. Emitter — AST node → PHP string

Directory: `phirescript/src/Compiler/Emitter/`

Each `NodeEmitter` implements:
```php
interface NodeEmitter {
    public function supports(object $node, EmitContext $ctx): bool;
    public function emit(object $node, EmitContext $ctx): string;
}
```

**Pattern — if-like statement emitter with chained clauses:**
```php
public function emit(object $node, EmitContext $ctx): string {
    $condition = $ctx->emitter->emit($node->condition->children[0], $ctx);
    $body = $this->emitBody($node->statements->children, $ctx);
    $code = "if ($condition) {\n $body\n}";

    foreach ($node->elseIfClauses as $clause) {
        $elseIfCond = $ctx->emitter->emit($clause->condition->children[0], $ctx);
        $elseIfBody = $this->emitBody($clause->statements->children, $ctx);
        $code .= " elseif ($elseIfCond) {\n $elseIfBody\n}";
    }

    if ($node->elseStatements !== null) {
        $elseBody = $this->emitBody($node->elseStatements->children, $ctx);
        $code .= " else {\n $elseBody\n}";
    }
    return $code;
}
```

**Rules:**
- Emit PHP that targets PHP 8.2+. No compatibility shims.
- Use `$this->emitBody(array $nodes, EmitContext $ctx)` (from `NodeEmitterAbstract`) to recursively emit child nodes.
- The emitter only produces strings — never mutates AST state.

---

## 10. Register the emitter

Verify in `phirescript/src/Compiler/Emitter.php` how emitters are registered and add an entry if needed. Many emitters are auto-discovered if they implement `NodeEmitter` — confirm the discovery mechanism before adding manual registration.

---

## 11. Sandbox — validate with a case

Directory: `PHire-Script-Sandbox/samples/success/case_N/`

1. Create the directory `case_N` (next sequential number — `ls samples/success/ | sort -V | tail -1`).
2. Write a `.ps` source file exercising the new construct with realistic, varied inputs.
3. Write `CaseValidation.php`:
   ```php
   class CaseValidation extends AbstractCaseValidation {
       public function execute() {
           $this->stopIfNoTest = false;
           $this->assertHasMessage(["✔ src/output/Foo.ps → src/compiled/Foo.php"]);
       }
   }
   ```
4. Write `FooTest.php` (PHPUnit) that `include`s the compiled `.php`, captures `get_defined_vars()`, and asserts observable outcomes (variable values, source string patterns).
5. Compile to verify: `php phirescript/bin/build samples/success/case_N src/compiled extra`
6. Inspect `src/compiled/Foo.php` to confirm the generated PHP is correct.
7. Run: `php bin/stretch --mode=success`

**Package naming:** `.ps` files that define a class/type/interface/trait must declare `pkg PHireScript.SamplesN` matching the case number exactly.

---

## 12. Quality checks

```bash
# From phirescript/ directory:
composer quality           # rector + cs-fixer + phpstan + phpunit (all in sequence)
vendor/bin/phpunit         # unit tests only

# From sandbox root:
php bin/stretch --mode=success
```

PHPStan runs at level 9. New files will typically produce the same pre-existing error classes:
- `missingType.iterableValue` on untyped `array` properties
- `missingType.generics` on `AbstractContext` params without `<T>` annotation
- `property.notFound` on `$node->someProperty` inside Binder sub-classes (Node base does not declare concrete properties)
- `nullsafe.neverNull` when using `?->children` / `?->params` on the left side of `??` — PHPStan infers non-null but runtime may differ; baseline it

These are baselined in `phirescript/phpstan.baseline.neon`. **Prefer surgical manual edits over `--generate-baseline`**: the regenerate command overwrites the entire file and can silently absorb unrelated regressions. Instead, run PHPStan with `--error-format=raw` to see exactly which new errors appeared, add each as a targeted entry, and remove entries for methods/properties that no longer exist:

```bash
# From phirescript/ directory:
vendor/bin/phpstan analyse --memory-limit=512M --error-format=raw 2>&1
```

Each baseline entry follows this schema (copy from any adjacent entry):
```yaml
-
    message: '#^Access to an undefined property PHireScript\\...\\Node\:\:\$myProp\.$#'
    identifier: property.notFound
    count: 1
    path: src/Compiler/Binder/Declaration/MyBinder.php
```

---

## 13. SOLID / DRY / KISS guidelines for this codebase

| Principle | How it applies here |
|---|---|
| **Single Responsibility** | One node class per construct. One resolver per trigger condition. One emitter per node type. Never merge two constructs into one class. |
| **Open/Closed** | Add new resolvers to an existing context's resolver list rather than modifying resolver logic. New constructs extend the pipeline, not replace it. |
| **Liskov** | All resolvers satisfy `ContextTokenResolver` fully — no partial implementations. |
| **Interface Segregation** | `ContextTokenResolver` is intentionally tiny (2 methods). Don't add methods to it. |
| **Dependency Inversion** | Contexts depend on `ContextTokenResolver`, not concrete resolvers. Resolvers are constructed inline; no DI container needed at this layer. |
| **DRY** | Reuse existing resolvers (`ComparisonExpressionResolver`, `EndOfLineResolver`, `VariableResolver`, etc.) rather than re-implementing. Scope contexts share the same body resolver list — copy it, don't invent a new one. |
| **KISS** | Each resolver has one `isTheCase` check and one `resolve` action. Keep `afterClose` logic to 3–5 lines. If a context's `handle` grows complex, extract a resolver. |

**Performance:**
- Resolver lists are arrays of pre-constructed objects — instantiated once in the constructor, never per-token.
- `isTheCase` checks must be O(1): string equality, array `in_array` with `strict: true`, or `instanceof`.
- `afterClose` peeks one token ahead via `getNextTokenAfterCurrent()` — do not scan more than one token ahead.
- Nodes are plain PHP objects with public properties — no getters/setters. Direct property access is idiomatic and faster here.
- Generated PHP must be idiomatic PHP 8.2: typed properties, `match`, named args where appropriate. Never emit `isset()` guards or `??` fallbacks that the compiler already knows are unnecessary.

---

## 14. Changelog

| Feature | What was learned |
|---|---|
| Comparison operators (`==`, `===`, `!=`, `!==`, `>=`, `<=`, `>`, `<`) | Context disambiguation for `<`/`>`: scanner is neutral, resolvers decide by context. `BinaryExpressionContext` handles chaining for `&&`/`\|\|`. `walk(-1)` in `afterClose` re-presents non-value closing tokens to parent. Validator must track paren depth to not count `<`/`>` inside `()`. |
| `elseif` blocks | Chained clause pattern: parent node holds `array $elseIfClauses`; resolver uses int key + direct mutation (`$ifNode->elseIfClauses[] = $node`). `ElseIfScopeContext.afterClose` exits two levels (ElseIfContext + optionally IfContext). `IfScopeContext.afterClose` must check for both `'else'` and `'elseif'` before deciding to exit `IfContext`. When adding constructor params to an existing node, fix all unit tests that pass positional args. |
| Binder refactoring + unit testing | Binder sub-classes access `$binder->globalTable` and `$binder->program` (both public). `TypeRegistrationBinder` must be index 0 so all types are in `SymbolTable` before body-level binders run. `CheckerException` must not use `readonly` on `$line`/`$column` — PHP 8.3 fatal. PHPStan baseline: surgical manual edits, not `--generate-baseline`. |
| Checker refactoring | Checker sub-classes add `assert($node instanceof SpecificNodeClass)` at the top of `check()` to narrow the `Node` type — PHPStan requires it at level 9. `ReturnTypeNode` has no `__toString`; use `->types` array directly (e.g. `implode('|', $method->returnType->types)`). `PropertyNode` uses `->value` (`?Node`) not `->defaultValue`. `ClassNode::$body` is `?ClassBodyNode` — guard with `$node->body !== null ? $node->body->children : []` (PHPStan 2 flags `?->prop ?? []` on the left of `??`). Test helper `StringableReturnType` must populate `$this->types = explode('|', $typeString)` rather than implement `__toString`. |
| Validator refactoring | Pattern mirrors Checker/Binder: `ValidatorRule` interface with `handleToken(Token, CompilerValidator)` + `afterTokens(CompilerValidator)`. Main `Validator` exposes `public bool $mustHavePkg` for cross-rule shared state. Rules: `ForbiddenTokenRule` (Tokens/), `ObjectCountRule` + `PackageRule` + `BracketBalanceRule` (Structure/). Ordering in `$rules` controls `afterTokens` order: BracketBalance before PackageRule preserves original validation sequence. `Token::$value` is `readonly mixed` — `(string)` cast triggers `cast.string` at PHPStan level 9; baseline it. PHP allows a class name and a namespace of the same FQN to coexist (e.g., `PHireScript\Compiler\Validator` class + `PHireScript\Compiler\Validator\Structure\` namespace). |
