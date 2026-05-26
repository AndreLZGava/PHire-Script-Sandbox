# Data Model: Expansão de Arrow Functions

**Branch**: `001-expand-arrow-functions` | **Date**: 2026-05-25

---

## Nós AST existentes (sem mudança de estrutura)

### `ArrowFunctionNode`
```
ArrowFunctionNode
  token:       Token           // posição; valor = '(' (token da abertura)
  parameters:  ?ParamsListNode // lista de parâmetros (null = sem parâmetros)
  returnType:  ?ReturnTypeNode // tipo de retorno declarado
  bodyCode:    ?MethodScopeNode // corpo entre { }
```

### `ParamsListNode`
```
ParamsListNode
  token:  Token             // token de posição
  params: ParamArgumentNode[] // lista ordenada de parâmetros
```

### `ParamArgumentNode`
```
ParamArgumentNode
  token:            Token    // token de posição
  types:            string[] // tipos declarados (union = mais de 1)
  name:             ?string  // nome da variável (sem $)
  value:            mixed    // valor default (null = sem default)
  resolvedTypeInfo: array    // metadados de resolução de tipo
```

### `ReturnTypeNode`
```
ReturnTypeNode
  types: string[] // tipos de retorno (union = mais de 1, já suportado)
```

### `MethodScopeNode`
```
MethodScopeNode
  statements: Node[] // statements dentro de { }
```

---

## Novo componente de parser

### `ArrowFunctionOpeningParensResolver` *(novo)*
```
ArrowFunctionOpeningParensResolver
  isTheCase: $token->isOpeningParenthesis()
             (sem verificação de token anterior)
  resolve:   cria ParamsListNode + entra ParameterListContext
```

Registrado como primeiro resolver em `ArrowFunctionDeclarationContext.resolvers['parameters']`,
substituindo `OpeningParamsDeclarationResolver`.

---

## Novo componente de checker

### `ArrowFunctionChecker` *(novo)*
```
ArrowFunctionChecker  [CompilerPass(order: N)]
  check(ArrowFunctionNode, Checker):
    - corpo vazio + returnType != Void → CheckerException
    - tem return com valor + returnType == Void → CheckerException
    - returnType != Void + nenhum return no corpo → CheckerException
```

---

## Contratos de interface

### `ArrowFunctionEmitter` — saída esperada

| Cenário PHireScript | PHP gerado |
|---|---|
| `(): Void => {}` | `function(): void {}` |
| `(Int n): Int => { return n * 2 }` | `function(int $n): int { return $n * 2; }` |
| `(Float p, Float t): Float => { return p * t }` | `function(float $p, float $t): float { return $p * $t; }` |
| `(String nome = "x"): String => { return nome }` | `function(string $nome = "x"): string { return $nome; }` |
| corpo referencia `$desconto` do escopo externo | `function(...) use ($desconto): ...` |
| union type `String\|Int` no parâmetro | `string\|int $param` |
| union type `String\|Int` no retorno | `: string\|int` |
