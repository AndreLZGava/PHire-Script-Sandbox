# Research: Method Chaining

**Date**: 2026-06-03

---

## Bloqueador raiz — diagnóstico confirmado

### Causa 1: VariableReferenceResolver rejeita variável antes de `.`

**Arquivo**: `phirescript/src/Compiler/Parser/Ast/Resolver/Expressions/Types/VariableReferenceResolver.php:18`

```php
$parseContext->tokenManager->getNextTokenAfterCurrent()->value !== '.'
```

Esta condição foi adicionada provavelmente para evitar que `VariableReferenceResolver` interceptasse variáveis dentro de expressões de acesso a propriedade de objeto. Mas tem o efeito colateral de impedir que qualquer variável seguida de `.` seja reconhecida — `variableOnFocus` nunca é setado.

**Fix**: Remover a condição `!== '.'`. O `VariableReferenceNode` deve ser criado independentemente do próximo token, permitindo que o foco seja estabelecido antes do `.`.

### Causa 2: DotResolver (Statements) com resolve() vazio

**Arquivo**: `phirescript/src/Compiler/Parser/Ast/Resolver/Statements/DotResolver.php`

O `resolve()` está completamente vazio. O `.` é reconhecido mas nenhuma ação é tomada — o foco não é transferido para o último nó filho do contexto.

**Fix**: Implementar `resolve()` para pegar `end($context->children)` e chamar `setVirtualVariable()`.

### Causa 3: FunctionCallResolver não atualiza o foco para o próximo elo

**Arquivo**: `phirescript/src/Compiler/Parser/Ast/Resolver/Expressions/FunctionCallResolver.php:87`

```php
//$parseContext->variables->setVirtualVariable($function);  // COMENTADO
```

Esta linha está comentada — após resolver um método, o `FunctionNode` resultante não se torna o novo foco. Sem isso, o segundo método da chain não consegue resolver seu tipo de entrada.

**Fix**: Descomentar e ativar `setVirtualVariable($function)` após a criação do FunctionNode.

---

## Mecanismos já funcionais (não precisam ser criados)

### FunctionCallContext.canClose()
Já fecha no `.`:
```php
return $token->isDot() || $token->isEndOfLine();
```
Significa que quando o parser termina de processar `replace(...)`, o contexto fecha no `.` seguinte e o próximo token (nome do próximo método) chega no contexto pai com o foco já atualizado.

### overrideVariableOnFocus() em FunctionCallResolver
Já existe a lógica de substituir o tipo do foco pelo tipo de retorno do método:
```php
$function->variableBase->type = $newVariable;
```
Funciona para `Array`, `String`, `Int`, `Float`, `Object`, `Bool`. Precisa ser estendido para SuperTypes e `Null`.

### getFunctionFromLastExecution() em SymbolTableManager
Mecanismo de lookup por tipo de retorno da última chamada já existe — só precisa ser alimentado corretamente pelo foco.

---

## Decisões de design confirmadas

| Questão | Decisão | Fonte |
|---|---|---|
| Emissão PHP de chains | Inline nested — sem variáveis temporárias | Conversa de design |
| Variável de origem em chain com atribuição | Preservada, inalterada | Conversa de design |
| Sobrescrita de variável | Explícita: `myvar = myvar.method()` | Conversa de design |
| Chain sem atribuição | Válida apenas em contexto de expressão ou com método `!` | Conversa de design |
| Retorno nullable + chain | Checker error por padrão; `?.` como opt-in explícito | Alinhamento TypeScript style |
| Chain sobre literal sem destino | CheckerException (não warning) | Conversa de design |
| Mixed bloqueia chain direta | CheckerException | Conversa de design |
| Safe navigation | `?.` como token único após `)` | Sem conflito com `method?` |

---

## Mapeamento de tipos para getNewVirtualVariable — extensão necessária

Tipos presentes em `returnOfPhpExecution` dos TypeMethods que ainda não são suportados:

| Tipo | Aparece em | Node a criar |
|---|---|---|
| `Null` | `between`, `toNumber`, `indexOf`, `lastIndexOf` | `NullNode` |
| `Mixed` | `first`, `last`, `get`, `find`, `reduce` de Array | `LiteralNode` genérico |
| `Void` | `sort!`, `addEnd!`, `addStart!`, `destroy!`, `each` | Sem node — sinaliza fim de chain |
| SuperTypes (`Email`, `Uuid`, etc.) | `SuperTypes/EmailMethods.php` etc. | `LiteralNode` com tipo |

**Decisão**: `Null` e `Void` recebem tratamento especial no Checker (não precisam de node virtual para chain — a chain termina). `Mixed` recebe `LiteralNode` genérico mas o Checker bloqueia chain direta após ele. SuperTypes recebem `LiteralNode` com o nome do SuperType para manter o lookup funcionando.

---

## Multi-line chain — mecanismo

Quando o parser encontra `.` em `DotResolver`:
1. Verificar se o token anterior é EOL via `TokenManager.lookBehind()`
2. Se for EOL, a EOL já foi processada e o contexto não foi fechado (porque estamos dentro de AssignmentContext ainda)
3. O `.` sinaliza continuação — `setVirtualVariable(end($context->children))`

Isso funciona porque `AssignmentContext.canClose()` retorna true apenas para EOL. Se o EOL que precede o `.` de continuação não foi o último token de um statement completo, o contexto ainda está aberto. A questão é: como o parser sabe que aquela EOL não é fim de statement?

**Solução**: O `DotResolver` em AssignmentContext (e ProgramContext) precisa checar `lookBehind()` para EOL. Se encontrar EOL + `.`, não encerra o statement — o `.` assume o papel de sinalizador de continuação. O EOL intermediário é simplesmente ignorado. Isso requer que o `EndOfLineResolver` não feche o AssignmentContext quando o próximo token não-whitespace for `.`.

**Alternativa mais simples**: `FunctionCallContext.afterClose()` já tem:
```php
if ($token->isEndOfLine()) {
    $parseContext->contextManager->exit();
}
```
Se o token for `.` (não EOL), o contexto não fecha automaticamente. Portanto chains inline já funcionam se os fixes do bloqueador raiz forem aplicados. Multi-line chain requer apenas que o `EndOfLineResolver` dentro de `FunctionCallContext` não encerre o contexto pai se o próximo token for `.`.
