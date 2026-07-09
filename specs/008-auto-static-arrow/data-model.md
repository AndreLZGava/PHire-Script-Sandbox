# Data Model: Automatic `static` Inference on Arrow Functions

**Feature**: 008-auto-static-arrow | **Date**: 2026-07-09

## Existing Nodes (no changes)

### ArrowFunctionNode
```
ArrowFunctionNode
  token: Token
  bodyCode: ?MethodScopeNode   ← walk target
  parameters: ?ParamsListNode
  returnType: ?ReturnTypeNode
```

### MethodScopeNode
```
MethodScopeNode
  token: Token
  children: object[]   ← contains statements, expressions, nested nodes
```

### ThisExpressionNode
```
ThisExpressionNode extends Expression
  (no fields — presence alone is the signal)
```

## Detection Walk

```
containsThisExpression(nodes: object[]): bool
├── ThisExpressionNode       → TRUE  (found)
├── ArrowFunctionNode        → SKIP  (independent scope boundary)
├── ReturnNode               → recurse into ReturnNode.expression
├── MethodScopeNode          → recurse into MethodScopeNode.children
└── anything else            → SKIP  (no known child collection)
```

## Emit Decision Table

| Arrow function body contains `ThisExpressionNode` | Emitted prefix |
|----------------------------------------------------|----------------|
| No                                                 | `static function` |
| Yes                                                | `function` |

## No New Nodes or Schemas

This feature is a pure emit-phase logic change. No new AST nodes, no new data structures, no new configuration keys.
