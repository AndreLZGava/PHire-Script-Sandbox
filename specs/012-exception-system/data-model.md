# Data Model: PHireScript Exception System V1

**Date**: 2026-07-22 | **Feature**: [plan.md](plan.md)

## AST Nodes (new)

### ExceptionNode

```
ExceptionNode
├── token: Token                     // 'exception' keyword token
├── name: string                     // 'ValidationException'
├── extends: ?ClassExtendsNode       // reuse existing node; null = extends \Exception
├── messageTemplate: ?string         // raw template, e.g. 'Invalid field: {field}'
├── properties: ExceptionPropertyNode[]
└── hasCustomConstructor: bool
```

Emits: `class {name} extends {parent|Exception} { ... }`

---

### ExceptionPropertyNode

```
ExceptionPropertyNode
├── token: Token
├── name: string                     // 'field'
└── type: TypeNode                   // 'String' → 'string', 'Int' → 'int', etc.
```

Emits: `public readonly {type} ${name}` as promoted constructor parameter.

---

### ExceptionCallNode

Models the throw-site expression: `ValidationException(field: 'email', cause: e)`

```
ExceptionCallNode
├── token: Token                     // exception name identifier
├── typeName: string                 // 'ValidationException'
├── args: NamedArgNode[]             // all named arguments from throw site
├── explicitMessage: ?ExpressionNode // value of message: arg (if present)
├── cause: ?ExpressionNode           // value of cause: arg (maps to PHP 'previous:')
├── context: ?ExpressionNode         // value of context: arg (array literal)
└── code: ?ExpressionNode            // value of code: arg
```

Emits: `new \{FQN}({args...})`

Special param rewrites at emit time:
- `cause: e` → `previous: $e`
- `message: 'x'` → `message: 'x'` (passed through; overrides template)
- `context: {...}` → `context: [...]`
- `code: 1001` → `code: 1001`

---

### ThrowsAnnotationNode

Attached to `FunctionNode` and `MethodDeclarationNode` as `throwsTypes: string[]`.

```
ThrowsAnnotationNode
├── token: Token                     // 'throws' keyword token
└── types: string[]                  // ['UserNotFoundException', 'DatabaseException']
```

Not emitted to PHP (checked at compile time only).

---

## Modified Nodes

### MethodDeclarationNode

Add field: `public array $throwsTypes = []` — populated by `ThrowsResolver`.

### FunctionNode

Add field: `public array $throwsTypes = []` — populated by `ThrowsResolver`.

---

## Type Registry

`TypeRegistrationBinder` currently registers `ClassNode` and `InterfaceNode`. Add `ExceptionNode`:

```php
if ($statement instanceof ExceptionNode) {
    $binder->globalTable->registerTypeDefinition($statement->name, $statement);
}
```

This allows `ThrowsAnnotationChecker` to resolve exception type names to their declarations.

---

## Checker Model

### ExceptionImmutabilityChecker

- Walks all `AssignmentNode` in all scopes.
- If left-hand side is `PropertyAccessNode` and the resolved object type is an `ExceptionNode`, emit a `CompileException`.
- Constraint: only direct property assignments; method calls are not checked in V1.

### ExceptionInstantiationChecker

- Walks all expression nodes (function calls, new-like calls) that resolve to an `ExceptionNode` type.
- If the parent node is NOT a `ThrowStatementNode`, emit a `CompileException`.

### ThrowsAnnotationChecker

- Builds call-table: `{qualifiedName} → throwsTypes[]` from all parsed function/method nodes.
- For each call site in any scope, resolves callee's `throwsTypes`.
- Validates that the call site is enclosed in a `TryNode` with matching `HandleNode`(s), or the enclosing function/method re-declares the types in its own `throwsTypes`.
- Skips PHP-native callees (not in the type table).
- Scope: top-level functions, instance methods, static methods — all enforced equally.

---

## Emitter Shapes

### ExceptionEmitter — bare

```php
class {Name} extends \Exception
{
}
```

### ExceptionEmitter — with properties, no template

```php
class {Name} extends \{Parent}
{
    public function __construct(
        public readonly string $field,
        public readonly string $reason,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        public readonly array $context = [],
    ) {
        parent::__construct($message, $code, $previous);
    }
}
```

### ExceptionEmitter — with template `'Invalid field: {field}'`

```php
class {Name} extends \Exception
{
    public function __construct(
        public readonly string $field,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        public readonly array $context = [],
    ) {
        if ($message === '') {
            $message = sprintf('Invalid field: %s', $field);
        }
        parent::__construct($message, $code, $previous);
    }
}
```

### ThrowStatementEmitter — extended

```php
throw new \{FQN}(
    field: 'email',
    previous: $e,       // cause: → previous:
    context: ['k' => 'v'],
    code: 1001,
);
```

---

## Sandbox Case Layout

| Case | `.phs` files | CaseValidation assertion | Test assertion |
|------|-------------|--------------------------|----------------|
| case_80 | `Exceptions.phs` | messages: exception emitted | class extends Exception |
| case_81 | `Exceptions.phs` | messages: readonly ctor | readonly props on class |
| case_82 | `Throw.phs` | messages: throw emitted | throw new + named args |
| case_83 | `Template.phs` | messages: template emitted | sprintf in ctor |
| case_84 | `Checked.phs` | messages: error on unhandled | N/A (compile error case) |
| case_85 | `Immutable.phs` | messages: error on mutation | N/A (compile error case) |
