# Data Model: Method Chaining

**Date**: 2026-06-03

---

## Mudanças no AST

### FunctionNode — campos adicionados

```php
// phirescript/src/Compiler/Parser/Ast/Nodes/Declarations/FunctionNode.php
public bool $safeNavigation = false;    // true quando precedido por ?.
public bool $isChainLink = false;       // true quando parte de uma chain (não chamada isolada)
```

`$safeNavigation = true` instrui o FunctionEmitter a gerar o guard de null ao invés de emissão inline direta.

### ChainNode — NÃO criar

A chain não precisa de um nó próprio no AST. A estrutura já é representada por `FunctionNode.variableBase` apontando para o `FunctionNode` anterior — uma lista ligada implícita. Criar um `ChainNode` seria overhead desnecessário e violaria YAGNI.

---

## Estrutura da chain no AST (lista ligada via variableBase)

Para `result = mystring.replace('a','b').replace('c','d').length()`:

```
AssignmentNode
  left: VariableDeclarationNode (result)
  right: FunctionNode (length)          ← topo da chain
    method: BaseMethods(length)
    variableBase: FunctionNode (replace_2)
      method: BaseMethods(replace)
      variableBase: FunctionNode (replace_1)
        method: BaseMethods(replace)
        variableBase: VariableReferenceNode (mystring)
          type: StringNode
```

Esta estrutura já é gerada parcialmente pelo mecanismo existente. Os fixes apenas garantem que a lista ligada seja construída corretamente da base ao topo.

---

## Entidades do Checker

### ChainLink (conceito interno, não uma classe PHP)

Representa cada elo durante a validação do Checker:

| Campo | Tipo | Descrição |
|---|---|---|
| `node` | `FunctionNode` | O nó do elo |
| `inputType` | `string` | Tipo de entrada (tipo do variableBase) |
| `outputType` | `string[]` | `returnOfPhpExecution` do método |
| `isVoid` | `bool` | true se outputType é vazio ou ['Void'] |
| `isNullable` | `bool` | true se 'Null' está em outputType |
| `isMixed` | `bool` | true se outputType é ['Mixed'] |
| `safeNavigation` | `bool` | true se `?.` precede este elo |

---

## Tokens novos

| Token | Valor | isSafeNavigation() |
|---|---|---|
| SafeNavigation | `?.` | true |

Adicionado ao Scanner com padrão `\?\.` antes do padrão existente de `.`.

---

## VariableManager — sem mudanças estruturais

`setVirtualVariable()` já existe e é o mecanismo correto para atualizar o foco durante a chain. Nenhum campo novo necessário.

---

## SymbolTableManager — sem mudanças estruturais

`getFunctionFromLastExecution()` já suporta lookup por tipo de retorno. Precisa ser alimentado com o tipo correto após cada elo — isso é resolvido pelo fix do `FunctionCallResolver`.
