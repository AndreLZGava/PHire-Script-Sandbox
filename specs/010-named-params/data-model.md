# Data Model: Named Parameters (010)

**Date**: 2026-07-09

## New AST Node

### `NamedArgNode`

**Namespace**: `PHireScript\Compiler\Parser\Ast\Nodes\Expressions`

**File**: `phirescript/src/Compiler/Parser/Ast/Nodes/Expressions/NamedArgNode.php`

| Field | Type | Description |
|-------|------|-------------|
| `$token` | `Token` | The identifier token (inherited from `Node`) |
| `$paramName` | `string` | The parameter name as written in source (no `@` prefix) |
| `$value` | `?Node` | The argument value node; set when `NamedArgContext` closes |

**Invariants**:
- `$paramName` is never empty
- `$value` is always set before emission (guaranteed by `NamedArgContext` lifecycle)

---

## New Context

### `NamedArgContext`

**Namespace**: `PHireScript\Compiler\Parser\Ast\Context\Declarations`

**File**: `phirescript/src/Compiler/Parser/Ast/Context/Declarations/NamedArgContext.php`

**Role**: Handles the colon and the value expression of a single named argument.

**Resolver list** (in order):
1. `IgnoreColonResolver` — discards the `:` token
2. `StringLiteralResolver` — string values
3. `NumberLiteralResolver` — numeric values
4. `BoolLiteralResolver` — boolean values
5. `ArrayLiteralResolver` — array values
6. `VariableReferenceResolver` — variable references
7. `ExternalClassAccessResolver` — external class values
8. `ExternalMethodCallResolver` — external method call values

**Close condition**: `canClose()` returns `true` for `,` and `)` (i.e., after the value token is consumed, the next token belonging to the parent triggers close).

**`afterClose`**: Sets `$this->node->value = end($this->children)` on the `NamedArgNode`, then calls `$parseContext->contextManager->exit()`.

---

## New Resolver

### `NamedArgResolver`

**Namespace**: `PHireScript\Compiler\Parser\Ast\Resolver\Expressions`

**File**: `phirescript/src/Compiler/Parser/Ast/Resolver/Expressions/NamedArgResolver.php`

**Role**: Detects `identifier :` inside a params context and opens `NamedArgContext`.

| Method | Logic |
|--------|-------|
| `isTheCase` | `$token->isIdentifier() && $parseContext->tokenManager->getNextTokenAfterCurrent()->isColon()` |
| `resolve` | Create `NamedArgNode($token->value)`, enter `NamedArgContext($node)`, `$context->addChild($node)` |

**Position in `ParamsConsumptionContext`**: First resolver, before `ExternalClassAccessResolver`.

---

## Modified: `FunctionEmitter::normalizeParams()`

The method signature does not change. Internal logic is extended:

### New Step A — Style detection

```
$hasNamed    = at least one param instanceof NamedArgNode
$hasPositional = at least one param NOT instanceof NamedArgNode
```

### New Step B — Mixed style error

```
if ($hasNamed && $hasPositional) → CompileException("Cannot mix positional and named arguments in the same call", line, col)
```

### New Step C — Named path (replaces current positional iteration when $hasNamed)

```
1. Build sent-map: ['separator' => NamedArgNode, ...] keyed by paramName
2. Detect duplicates: if count(unique names) < count(sentParams) → CompileException("Duplicate named argument: {name}")
3. For each BaseParams $expected (in declaration order):
   $normalizedName = ltrim($expected->name, '@')
   if isset(sent-map[$normalizedName]):
     emit sent-map[$normalizedName]->value
   elseif $expected->required:
     throw CompileException("Missing required named argument: {$normalizedName}", line, col)
   else:
     use processDefaultValue($expected)
4. Check for unknown names: any key in sent-map not matching any BaseParams name → CompileException("Unknown named argument: {name}", line, col)
```

### Positional path

Unchanged — runs when `$hasNamed` is false.

---

## Relationship Diagram

```
ParamsConsumptionContext
  └─ resolvers[0]: NamedArgResolver          [NEW]
       └─ on match: creates NamedArgNode
                    enters NamedArgContext    [NEW]
                      └─ resolvers: IgnoreColonResolver + value resolvers
                         closes on , or )
                         sets NamedArgNode::$value

FunctionNode
  └─ params: ParamsNode
       └─ params[]: Node[]
            ├─ (positional) StringNode | NumberNode | VariableReferenceNode | ...
            └─ (named)      NamedArgNode { paramName, value: Node }

FunctionEmitter::normalizeParams()
  ├─ detects NamedArgNode in params array
  ├─ validates (mixed / duplicate / unknown / missing)
  └─ reorders by BaseParams declaration order → positional PHP output
```
