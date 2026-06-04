# Contracts: Chain Checker Rules

**Date**: 2026-06-03
**Implementado em**: `phirescript/src/Compiler/Checker/Expression/ChainConsistencyChecker.php`

---

## Regra 1 — Continuidade de tipo

**Trigger**: `FunctionNode` com `$isChainLink = true`

**Condição de erro**: O método chamado não existe no TypeMethods do tipo de entrada do elo.

**Exceção**:
```
CheckerException: "Method `{methodName}` does not exist for type `{inputType}`"
  line: {token->line}, column: {token->column}
```

**Exemplo de violação**:
```
mystring.length().toUpperCase()
// length retorna Int; toUpperCase não existe em IntMethods
```

---

## Regra 2 — Void termina a chain

**Trigger**: `FunctionNode` com `$isChainLink = true` cujo `variableBase` é um `FunctionNode` com `method->returnOfPhpExecution = []` (void)

**Condição de erro**: Qualquer elo após um método void.

**Exceção**:
```
CheckerException: "Cannot chain after void method `{voidMethodName}`"
  line: {token->line}, column: {token->column}
```

**Exemplo de violação**:
```
myArray.destroy!().length()
// destroy! é void; length não pode ser encadeado
```

---

## Regra 3 — Nullable requer `?.`

**Trigger**: `FunctionNode` com `$isChainLink = true` cujo elo anterior tem `Null` em `returnOfPhpExecution`, e `$safeNavigation = false`

**Condição de erro**: Chain direta (`.`) após método nullable sem uso de `?.`.

**Exceção**:
```
CheckerException: "Method `{nullableMethod}` may return `Null`. Use `?.` to propagate or assign and check before chaining."
  line: {token->line}, column: {token->column}
```

**Exemplo de violação**:
```
mystring.between('a', 'b').length()
// between pode retornar Null; precisa de ?.
```

**Caso válido**:
```
mystring.between('a', 'b')?.length()
// ?.  propaga null corretamente
```

---

## Regra 4 — Dead chain

**Trigger**: `FunctionNode` com `$isChainLink = true` (ou `FunctionNode` sobre literal) cujo nó pai não é `AssignmentNode`, condição de `IfNode`, ou argumento de outro `FunctionNode`. Método não é void.

**Condição de erro**: Resultado não-void sem destino.

**Exceção**:
```
CheckerException: "Dead chain — result of `{expression}` is never used. Assign to a variable or use inside an expression."
  line: {token->line}, column: {token->column}
```

**Exemplo de violação**:
```
'my string'.length()     // literal sem destino
mystring.toUpperCase()   // variável sem destino, resultado descartado
```

**Caso válido**:
```
'my string'.show!()      // void — efeito colateral é o objetivo
result = mystring.toUpperCase()   // tem atribuição
if(mystring.length() > 5)         // alimenta condição
```

---

## Regra 5 — Mixed bloqueia chain direta

**Trigger**: `FunctionNode` com `$isChainLink = true` cujo elo anterior tem `returnOfPhpExecution = ['Mixed']`

**Condição de erro**: Chain direta após método que retorna `Mixed`.

**Exceção**:
```
CheckerException: "Cannot chain directly after `Mixed` return from `{methodName}`. Assign to a variable and verify type before chaining."
  line: {token->line}, column: {token->column}
```

**Exemplo de violação**:
```
myArray.first().length()
// first retorna Mixed; tipo desconhecido em tempo de compilação
```

**Caso válido**:
```
item = myArray.first()
// depois verificar tipo antes de chamar métodos no item
```

---

## Regra adicional — `?.` desnecessário (Warning, não Error)

**Trigger**: `FunctionNode` com `$safeNavigation = true` cujo elo anterior NÃO tem `Null` em `returnOfPhpExecution`

**Comportamento**: Emitir warning via `Messenger::warning()`, não bloquear compilação.

```
Warning: "`?.` is unnecessary — method `{methodName}` never returns `Null`."
  line: {token->line}, column: {token->column}
```
