---
name: debug-compiler
description: Diagnose failures inside the compiler itself — wrong tokens, parse errors, binding gaps, checker false positives, emission bugs
metadata:
  type: skill
---

# Skill: Debug Compiler (Internals)

## Triggers

- "compiler crashes", "FatalErrorException", "CheckerException unexpected"
- "parse error on valid code", "token not recognized", "wrong PHP output"
- "Scanner misses token", "parser stops mid-file", "emitter produces wrong code"
- Debugging a regression after a code change to the compiler
- "php bin/debug", "inspect AST"

## When to Use

Use when debugging failures **inside the compiler codebase** (not a sandbox case failure).
For sandbox-level debugging (assertHasMessage failing, etc.), see the sandbox's `debug-compiler` skill.

## Repository Context

- Debug entry: `bin/debug` → `CompileMode::DEBUG`
- Error rendering: `src/Runtime/Exceptions/FatalErrorException.php`
- Compile errors: `src/Runtime/Exceptions/CompileException.php`
- Checker errors: `src/Runtime/Exceptions/CheckerException.php`
- Compiler unit tests: `tests/Compiler/`

## Key Patterns

### Step 1 — Use bin/debug to inspect tokens and AST

```bash
# From the sandbox root (phirescript/ is mounted):
php phirescript/bin/debug samples/success/case_N/MyFile.ps

# Or from inside phirescript/:
php bin/debug ../samples/success/case_N/MyFile.ps
```

`DEBUG` mode:
1. Runs Scanner → dumps `Token[]` with types, values, lines, columns
2. Runs Validator
3. Runs Parser → dumps the `Program` AST tree
4. Does NOT run Binder, Checker, or Emitter

Use this to:
- Check if a token is classified with the right `T_*` type
- See if the parser built the expected AST structure
- Identify at which token the parser stops

### Step 2 — Identify which phase fails

```
FatalErrorException with "Unexpected token..."   → Scanner or Validator
FatalErrorException with "Parse error..."        → Parser (context/resolver)
CheckerException "..."                           → Checker
FatalErrorException from nikic/php-parser        → Emitter produced invalid PHP
```

### Step 3 — Scanner issues

If a token is classified wrong or missing:

```bash
# Check Scanner regex patterns in src/Compiler/Scanner.php
# Run debug and look at the raw Token[] output
php bin/debug file.ps 2>&1 | grep "T_*"
```

Common scanner problems:
- New keyword not added to `$keywords` array → classified as `T_IDENTIFIER`
- Super type name not added to `$supertypes` array → classified as `T_IDENTIFIER`
- Regex order matters — more specific patterns must come before general ones

### Step 4 — Parser issues

If the AST is wrong or parse stops early:

```
php bin/debug file.ps
```

Look at:
- Is the new construct's Context being entered?
- Does `canClose()` fire at the right token?
- Are extra tokens being consumed without `walk()`?

Add temporary debug output in the failing Context:

```php
public function handle(Token $token, ParseContext $ctx): void
{
    // Temporary: dump incoming tokens
    dump("Context " . __CLASS__ . " received: " . $token->type . " = " . $token->value);
    // ...
}
```

Remove before committing.

### Step 5 — Binder / SymbolTable issues

If compiled PHP has wrong types or missing metadata:

```php
// In the failing Binder, add dump():
public function bind(object $node, Binder $dispatcher): void
{
    dump('Binding ' . $node->name . ', resolved: ' . $node->resolvedType);
    // ...
}
```

Check PassDiscovery order — if a Binder is not running, verify:
- `#[CompilerPass(order: N)]` is present
- The file is in `src/Binder/` (PassDiscovery scans this directory)
- Run `composer analyse` — PHPStan may show a visibility or type issue

### Step 6 — Emitter issues (wrong PHP output)

Generate a snapshot to see what the Emitter produces before nikic post-processing:

```bash
# Set PHireScript.json source to the failing case, then:
php bin/snapshot

# Read the .psc file produced:
cat src/output/MyFile.psc
```

If the `.psc` content looks correct but the final `.php` is wrong, the issue is in a Processor (nikic visitor). Read `src/Processors/`.

If the `.psc` content is wrong, the issue is in a `NodeEmitter`.

Add a test to `tests/Compiler/Emitter/` that directly emits the failing node:

```php
$node = new MyConstructNode();
$node->name = 'Test';
$ctx = new EmitContext(dev: true, ...);
$emitter = new MyConstructEmitter();
$this->assertSame('expected PHP', $emitter->emit($node, $ctx));
```

### Step 7 — nikic/php-parser post-processor issues

If the `.psc` is correct but the `.php` is wrong:

```php
// In PhpFileGeneratorHandler, the processor chain is:
SemicolonHandler → ReturnTypeHandler → AccessorHandler → VariablesHandler
→ NativeTypesHandler → FunctionsHandler → ObjectsHandler
```

Add debug output in the failing Processor's visitor methods:

```php
public function leaveNode(Node $node): mixed
{
    dump('Processing: ' . get_class($node));
    return null; // return null = keep node unchanged
}
```

### Running compiler unit tests

```bash
# All tests:
composer test

# Specific file:
vendor/bin/phpunit tests/Compiler/ParserTest.php

# Specific test:
vendor/bin/phpunit --filter testClassName tests/Compiler/ParserTest.php

# With coverage:
vendor/bin/phpunit --coverage-html coverage-report/
```

### PHPStan analysis

```bash
composer analyse
# or:
vendor/bin/phpstan analyse src --level 9
```

Level 9 catches type errors that wouldn't surface at runtime. If a Binder/Checker/Emitter compiles but PHPStan fails, the compiler's type contracts are violated.

## Critical Rules

1. **Remove `dump()` calls before committing** — they interfere with the output capture in the sandbox.
2. **FatalErrorException wraps all unhandled exceptions** — to get a raw stack trace, temporarily catch before FatalErrorException in `Compiler.php`.
3. **PHPStan level 9 must pass** — it's a hard gate. Do not suppress errors with ignoreErrors unless truly unavoidable.
4. **`.psc` snapshot shows pre-nikic output** — use it to separate Emitter bugs from Processor bugs.
5. **`processedBy` field on Token** — after parsing, each Token has a `processedBy` string set by the last Resolver that handled it. Inspect this when tracking parse flow.

## Common Mistakes

- Debugging in the sandbox instead of in the compiler → wrong layer, wrong tools
- Not checking `#[CompilerPass(order: N)]` first when a Binder/Checker seems to not run
- Assuming nikic error is a PHireScript bug → read the `.psc` file first
- Leaving `dump()` in the code → sandbox output capture breaks, assertHasMessage fails

## Validation Checklist

- [ ] `php bin/debug <file.ps>` shows expected Token[] and AST structure
- [ ] Snapshot (`.psc`) shows correct pre-PHP emission
- [ ] Final `.php` file is valid PHP (`php -l compiled.php`)
- [ ] All `dump()` calls removed
- [ ] `composer test` passes
- [ ] `composer analyse` (PHPStan level 9) passes

## Examples

See: [examples/](examples/)
