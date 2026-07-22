# Research: Expansão de Arrow Functions

**Branch**: `001-expand-arrow-functions` | **Date**: 2026-05-25

---

## 1. Causa raiz do bug de múltiplos parâmetros

### Decisão
Criar um resolver dedicado `ArrowFunctionOpeningParensResolver` em `ArrowFunctionDeclarationContext` que dispara em `(` sem depender do token anterior.

### Problema identificado

`OpeningParamsDeclarationResolver.isTheCase()` tem duas condições:

```php
return $token->isOpeningParenthesis() &&
    ($before->isIdentifier() || $before->isMagicMethod())   // condição 1
    || $before->isOpeningParenthesis() && $token->isType(); // condição 2
```

Para **métodos** (funciona corretamente):
- Token antes de `(` é o nome do método (identifier) → condição 1 dispara em `(`
- `ParameterListContext` é criado; dentro dele, `Float` é o primeiro token → `ArgumentResolver` processa `Float` corretamente como início de `ParamArgumentNode(types=['Float'])`

Para **arrow functions** (bug):
- Token antes de `(` é `=` (operador de atribuição) → condição 1 **não** dispara em `(`
- `(` não é tratado por nenhum resolver → o `(` está na `ArrowFunctionNode.token` (apenas posição), mas nenhuma `ParameterListContext` é criada no momento certo
- Condição 2 dispara quando chega `Float` (next token), com `before = (`
- `OpeningParamsDeclarationResolver.resolve(Float)` cria `ParamsListNode(token=Float)` — `Float` é armazenado como token de posição do nó, **não** como tipo de parâmetro
- `ParameterListContext` entra; próximo token `price` → `ArgumentResolver.resolve(price)` → `ParamArgumentNode(types=['price'])` — tipo errado!

O `@todo` no código confirma que o autor já sabia desse problema.

### Rationale
A solução mais limpa é um resolver dedicado para `(` dentro de `ArrowFunctionDeclarationContext`. Ele dispara em `(` independentemente do token anterior, cria `ParamsListNode` e entra em `ParameterListContext` — exatamente o que `OpeningParamsDeclarationResolver` faz na condição 1, sem a verificação do token anterior.

### Alternativas consideradas
- Adicionar contexto de arrow function à condição 1 do `OpeningParamsDeclarationResolver` — rejeitado porque aumenta o acoplamento de um resolver genérico com um contexto específico
- Usar condição 2 corretamente passando `Float` para dentro do `ParameterListContext` — rejeitado porque exigiria mudar o contrato do `processResolvers` em `ArrowFunctionDeclarationContext` para "reinjetar" o token

---

## 2. Tipo de retorno — union types

### Decisão
Sem mudanças necessárias no `ReturnTypeEmitter`.

### Rationale
`ReturnTypeEmitter.emit()` já itera `$node->types` e une com `|`, convertendo para lowercase. `ReturnTypeNode.types` é um array, então union types já são suportados estruturalmente. A verificação precisa ser apenas no **parser** (`ReturnTypeResolver`/`ReturnTypeContext`) para garantir que múltiplos tipos separados por `|` sejam coletados no array — o que provavelmente já funciona pois o mesmo mecanismo é usado em métodos de classe.

---

## 3. Captura automática de variáveis (`use`)

### Decisão
Implementar análise de referências no `ArrowFunctionEmitter`: coletar todas as `VariableReferenceNode` no corpo da arrow function, subtrair os nomes dos próprios parâmetros, e gerar `use ($var1, $var2)` para as referências restantes.

### Rationale
O `MethodScopeNode` (corpo) contém a árvore de statements. Cada referência a variável externa é um `VariableReferenceNode` ou similar. O emitter pode percorrer recursivamente os filhos do `MethodScopeNode` coletando referências. Comparar contra `$node->parameters->params` (os parâmetros declarados). O que sobrar vai no `use (...)`.

### Alternativas consideradas
- Análise de escopo no binder — rejeitado: o binder existe para symbol binding/type resolution; injetar lógica de captura de closure aqui acoplaria o binder a um detalhe de emissão PHP
- Exigir anotação manual no `.phs` — rejeitado: a spec define que o desenvolvedor não escreve `use` manualmente

---

## 4. Valores default em parâmetros

### Decisão
Reutilizar o `ArgumentAssignmentResolver` + `ArgumentAssignmentContext` já existentes. Para SuperTypes/MetaTypes/objetos importados, o valor default será emitido como a inicialização PHP correspondente pelo `EmitterDispatcher`.

### Rationale
`ArgumentAssignmentResolver` já trata `=` no parâmetro e entra em `ArgumentAssignmentContext`. O valor resolvido é armazenado em `ParamArgumentNode.value` (typed as `mixed`). O `ParamArgumentEmitter.emit()` já chama `$ctx->emitter->emit($node->value, $ctx)` para emitir o valor, delegando ao dispatcher correto. SuperType e MetaType já têm emitters registrados.

---

## 5. Validação no checker (void vs. non-void)

### Decisão
Criar `ArrowFunctionChecker` com `#[CompilerPass(order: N)]` registrado via `PassDiscovery`. Validar: (a) corpo vazio com retorno não-Void; (b) `return valor` em função Void; (c) ausência de `return` em função não-Void.

### Rationale
`MethodReturnChecker` já faz validação análoga para métodos — o mesmo padrão é aplicado para arrow functions. A lógica de inspeção do `MethodScopeNode` para presença/ausência de `return` pode ser extraída/reutilizada.
