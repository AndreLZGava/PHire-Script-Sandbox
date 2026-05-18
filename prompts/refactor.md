# PHireScript — Refactoring Prompt

You are working on the **PHireScript** compiler, a PHP transpiler located at `phirescript/src/`.
The compiler pipeline is: Scanner → Validator → Parser → Binder → Checker → Emitter → PhpFileGenerator.

Apply the refactors below **one section at a time**. For each item: read the file, make the change,
run `composer quality` to verify no regressions, and confirm before moving to the next.

---

## 1. Code Duplication

### 1.1 Body emission pattern duplicated across ClassEmitter, TraitEmitter, InterfaceEmitter

**Files:**
- `src/Compiler/Emitter/NodeEmitters/ClassEmitter.php`
- `src/Compiler/Emitter/NodeEmitters/TraitEmitter.php`
- `src/Compiler/Emitter/NodeEmitters/InterfaceEmitter.php`

**Problem:** All three emitters iterate over `$node->body` twice — once for properties and once for
methods — with the same pattern repeated in each class.

**Fix:** Extract the pattern into a protected `emitBodyMembers(array $body, EmitContext $ctx): string`
method on the base `NodeEmitter` class. Each emitter then calls it instead of duplicating the loop.

```php
// Base NodeEmitter — add this method
protected function emitBodyMembers(array $body, EmitContext $ctx): string
{
    $code = '';
    foreach (array_filter($body, fn($m) => $m instanceof PropertyNode) as $prop) {
        $code .= $ctx->emitter->emit($prop, $ctx);
    }
    foreach (array_filter($body, fn($m) => $m instanceof MethodDeclarationNode) as $method) {
        $code .= $ctx->emitter->emit($method, $ctx);
    }
    return $code;
}
```

---

### 1.2 Duplicated `instanceof` loops in Binder — Pass 1 and Pass 2

**File:** `src/Compiler/Binder.php` lines ~51–67

**Problem:** Two separate `foreach` loops iterate over `$program->statements` with the same
`instanceof ClassNode || instanceof InterfaceNode` filter.

**Fix:** Filter the list once into `$classLike`, then loop over it twice.

```php
$classLike = array_filter(
    $program->statements,
    fn($n) => $n instanceof ClassNode || $n instanceof InterfaceNode
);

foreach ($classLike as $node) {
    $this->globalTable->registerTypeDefinition($node->name, $node);
}
foreach ($classLike as $node) {
    $this->bindClassBody($node);
}
```

---

### 1.4 Duplicated return-type validation in Checker

**File:** `src/Compiler/Checker.php` lines ~130–150

**Problem:** Two identical `if` blocks validate `mustBeBool` and `mustBeVoid` with copy-pasted
structure.

**Fix:** Extract into a private helper:

```php
private function assertReturnType(array $returnMethod, object $prop, string $type): void
{
    $flag = 'mustBe' . $type;
    if ($prop->$flag && (\count($returnMethod) > 1 || \current($returnMethod) !== $type)) {
        throw new CheckerException(
            "Method must return {$type}.",
            $prop->line,
            $prop->column
        );
    }
}
```

---

## 3. Hardcoded Values and Magic Strings

### 3.1 Type mapping arrays defined inline in Binder

**File:** `src/Compiler/Binder.php` lines ~109–135

**Problem:** Arrays of primitives, meta-types, and super-types are built inline inside a method on
every call. They never change at runtime.

**Fix:** Promote to private class constants:

```php
private const PRIMITIVE_MAP = [
    'String' => 'string',
    'Int'    => 'int',
    'Float'  => 'float',
    'Bool'   => 'bool',
    'Null'   => 'null',
    'Void'   => 'void',
    'Mixed'  => 'mixed',
    'Any'    => 'mixed',
];

private const META_TYPES = ['Date', 'Currency', 'Phone'];

private const SUPER_TYPES = [
    'Email', 'Ipv4', 'Ipv6', 'Uuid', 'Color', 'Url',
    'CardNumber', 'Cron', 'Cvv', 'Duration', 'ExpiryDate',
    'Json', 'Mac', 'Slug',
];
```

---

### 3.2 Magic number defaults in TokenManager

**File:** `src/Compiler/Parser/Managers/TokenManager.php` lines ~33–39

**Problem:** Both `getLeftTokens()` and `getProcessedTokens()` default to `100` with no explanation.

**Fix:**
```php
private const DEFAULT_TOKEN_WINDOW = 100;

public function getLeftTokens(int $limit = self::DEFAULT_TOKEN_WINDOW): array { ... }
public function getProcessedTokens(int $limit = self::DEFAULT_TOKEN_WINDOW): array { ... }
```

---

## 4. Code Smells

### 4.1 `gettype()` used instead of `is_int()`

**File:** `src/Compiler/Emitter/Declarations/FunctionEmitter.php` line ~132

**Problem:**
```php
if (\gettype($paramName) !== 'integer') { ... }
```

**Fix:**
```php
if (!is_int($paramName)) { ... }
```

---

### 4.4 Inconsistent string escaping in StringMethods

**File:** `src/Runtime/DefaultOverrideMethods/Types/StringMethods.php` lines ~77 and ~89

**Problem:** Some method strings use double backslash (`\\ltrim`) and others use single (`\rtrim`).
In PHP double-quoted strings both are valid but inconsistent.

**Fix:** Pick one style and apply it uniformly across all PHP code template strings in the file.

---

### 4.5 Generic `Exception` thrown instead of domain-specific exceptions

**Files:**
- `src/Compiler/Checker.php` lines ~66, ~77, ~116, ~165
- `src/DependencyGraphBuilder.php` lines ~47, ~78, ~87, ~147
- `src/Compiler/Processors/PhpFileGeneratorHandler.php`

**Problem:** PHireScript already defines `CompileException` and `CheckerException`. Throwing the
base `\Exception` class makes it impossible for callers to catch specific failure modes.

**Fix:** Replace every `throw new \Exception(...)` inside compiler phases with the appropriate
domain exception. Use `CompileException` for scanner/parser errors (with `$line` and `$column`)
and `CheckerException` for semantic errors.

---

### 4.6 Commented-out code block in ClassEmitter

**File:** `src/Compiler/Emitter/NodeEmitters/ClassEmitter.php` lines ~31–50

**Problem:** A ~20-line block of commented-out code remains in production. It signals incomplete
work but provides no context for why it was disabled.

**Fix:** Remove it completely. If the feature is planned, track it as an issue rather than leaving
dead code in the file.

---

## 5. SOLID Violations

### 5.2 OCP — Checker's `ensureReturnsForMethods` is closed to extension

**File:** `src/Compiler/Checker.php` lines ~130–150

**Problem:** Adding a new return-type constraint (e.g., `mustBeString`) requires modifying this
method directly.

**Fix:** Replace the explicit flags with a map or a list of `[flag => expectedType]` pairs so new
constraints can be added without changing the method body:

```php
$returnConstraints = [
    'mustBeBool' => 'Bool',
    'mustBeVoid' => 'Void',
];

foreach ($returnConstraints as $flag => $expectedType) {
    $this->assertReturnType($returnMethod, $prop, $flag, $expectedType);
}
```

---

### 5.3 DIP — Checker receives its `$table` dependency late

**File:** `src/Compiler/Checker.php` lines ~26 and ~48

**Problem:** `$this->table` is a property set inside the `check()` method rather than injected
via the constructor. This hides the dependency and makes the class impossible to use (or test)
without calling `check()` first.

**Fix:** Inject via constructor:
```php
public function __construct(private SymbolTable $table) {}
```

---

### 5.4 LSP — AbstractContext template methods with empty bodies

**File:** `src/Compiler/Parser/Ast/Context/AbstractContext.php` lines ~49–61

**Problem:** Template methods have empty default implementations. A subclass that forgets to
override one will silently do nothing, violating the expected behavior.

**Fix:** Either make the methods `abstract` (forcing subclasses to implement them) or throw a
`\LogicException` in the default body to surface the oversight immediately.
