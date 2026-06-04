# PHireScript Compiler — Pain Points & Proposed Solutions

> Registrado após a implementação de method chaining (specs/003-method-chaining).
> Cada ponto descreve um problema real encontrado durante o desenvolvimento,
> com contexto técnico e proposta de solução ou melhoria.

---

## 1. Cache silencioso mascarando bugs

### O que aconteceu

O compilador tem um `CacheManager` que persiste tokens, ASTs serializados e snapshots `.psc` em `.cache/`. Durante o desenvolvimento, um snapshot `.psc` antigo (com output errado) estava sendo copiado para `src/compiled/` pelo orchestrator, fazendo parecer que os fixes não tinham efeito. Rodadas do `bin/build` também retornavam o resultado antigo porque os tokens estavam em cache e o hash do arquivo ainda era considerado válido.

O pior: o compilador rodava sem erro e gerava output — mas o output era do cache, não do código corrigido.

### Por que é perigoso

Silêncio. Não há nenhuma indicação visual de que o resultado veio do cache. O developer (humano ou IA) debugga código que não está nem sendo executado.

### Proposta

**A. Banner visual de cache hit no modo `dev`**

Quando um arquivo é compilado a partir do cache (tokens ou AST), emitir uma linha `[CACHE HIT]` no output do compilador no modo `dev: true`. Exemplo:

```
[CACHE HIT] samples/success/case_42/StringChain.ps → tokens cached
✔ samples/success/case_42/StringChain.ps → src/compiled/StringChain.php  [3ms]
```

Isso torna o comportamento observável sem precisar inspecionar os arquivos do cache.

**B. Flag `--no-cache` no `bin/build`**

Adicionar suporte a `--no-cache` que bypassa todos os níveis de cache para uma compilação limpa. Útil durante desenvolvimento ativo de features do compiler.

```bash
php phirescript/bin/build --no-cache
```

**C. Invalidação automática por checksum do compiler**

Se qualquer arquivo em `phirescript/src/` for mais novo que o cache, invalidar o cache inteiro. Hoje só o checksum do arquivo `.ps` é verificado — mudanças no compiler em si não invalidam o cache.

---

## 2. Debug invisível — output suprimido pelo output buffer

### O que aconteceu

O compilador usa `ob_start()` / `ob_get_clean()` internamente (em `SuccessMode`, `FileCompiler`, etc.) para capturar o output do compilador e alimentar `assertHasMessage`. Como consequência, qualquer `echo`, `print`, `var_dump`, ou `fwrite(STDERR)` inserido para debug dentro do pipeline de compilação é capturado e silenciado — nunca chega ao terminal.

Tentativas de usar `file_put_contents('/tmp/debug.log', ...)` dentro de classes do compilador também falharam porque o processo rodava em contexto diferente do esperado.

### Por que é perigoso

Impossibilita o ciclo clássico de debug "adicionar print → rodar → ver output". Leva o developer a criar scripts PHP externos complexos só para inspecionar o estado interno, aumentando muito o tempo de ciclo.

### Proposta

**A. `Debug::dump()` que escreve em arquivo dedicado**

Criar um método `Debug::dump(mixed $value, string $label = '')` em `Helper/Debug/Debug.php` que escreve em `/tmp/phirescript_debug.log` via `file_put_contents(..., FILE_APPEND)`. Não passa pelo output buffer. Pode ser chamado de qualquer ponto do pipeline.

```php
Debug::dump($parseContext->variables->getVariableOnFocus(), 'focus after dot');
// /tmp/phirescript_debug.log: [focus after dot] VariableReferenceNode(mystring)
```

**B. `bin/debug` com saída de AST formatada**

O `bin/debug` atual apenas re-executa o compilador em modo DEBUG e mostra o PHP gerado. Deveria ter uma flag `--ast` que serializa o `Program` resultante do Parser em uma representação legível:

```bash
php phirescript/bin/debug samples/test.ps --ast
```

Output esperado:
```
Program
  PackageNode [PHireScript.Test]
  AssignmentNode
    left: VariableDeclarationNode [result] type=null
    right: FunctionNode [length]
      variableBase: FunctionNode [replace]
        variableBase: VariableReferenceNode [mystring] type=String
        params: ['is', 'is really']
```

**C. `bin/debug` com saída de tokens**

```bash
php phirescript/bin/debug samples/test.ps --tokens
```

Lista cada token com tipo, valor, linha e coluna. Essencial para debugar o Scanner quando um novo token é adicionado (ex: `T_SAFE_NAV`).

---

## 3. `DotResolver` dentro de `FunctionCallContext` clobbering o foco

### O que aconteceu

`FunctionCallContext.canClose()` retorna `true` quando o token é `.`. O `ContextManager.handle()` chama `context.handle(token)` **antes** de checar `canClose()`. Isso significa que quando `.` chega no `FunctionCallContext`, o `DotResolver` dentro dele roda primeiro — e `DotResolver.resolve()` faz `setVirtualVariable(end($context->children))`. Mas `children` no `FunctionCallContext` são os **parâmetros** da função (não o FunctionNode em si), então o foco era sobrescrito com o último param da chamada anterior (`StringNode('is really')`), destruindo o foco correto.

### Por que é perigoso

É um bug de interação sutil entre `handle()` e `canClose()` que depende da ordem de execução no `ContextManager`. Qualquer Resolver que reage a um token de fechamento pode ter o mesmo problema.

### Proposta

**A. Solução implementada — `DotResolver` com contexto ciente**

Quando o `DotResolver` está dentro de um `FunctionCallContext`, deve usar `$context->node` (o próprio FunctionNode) como novo foco, não `end($context->children)`. Implementado na feature 003.

**B. Refactor estrutural — separar `onClosingToken()` de `handle()`**

Uma solução mais robusta seria adicionar um método `onClosingToken(Token $token, ParseContext $ctx)` na `AbstractContext`, chamado pelo `ContextManager` quando `canClose()` retorna `true`, **antes** do `exit()`. Resolvers que precisam de comportamento diferente no token de fechamento implementariam esse método ao invés de serem chamados via `handle()`.

```php
// ContextManager.handle:
$this->current->handle($token, $parseContext);
if ($this->current->canClose($token, $parseContext)) {
    $this->current->onClosingToken($token, $parseContext); // novo
    $this->current->onClose($token, $parseContext);
    $this->exit();
    $current->afterClose($token, $parseContext);
}
```

Isso tornaria explícito que o token de fechamento tem dois papéis distintos.

---

## 4. `AssignmentContext` usando `children[0]` ao invés de `end(children)`

### O que aconteceu

`AssignmentContext.handle()` atualizava `$this->node->right = $this->children[0]` a cada token processado. Como `children[0]` é sempre o **primeiro** filho adicionado (a variável base, ex: `VariableReferenceNode(mystring)`), o `right` do `AssignmentNode` nunca avançava para o `FunctionNode` adicionado depois — mesmo que `FunctionCallResolver` tivesse corretamente chamado `addChild(FunctionNode)`.

O resultado: o emitter recebia `right = VariableReferenceNode` e emitia `$processed = $mystring` ignorando a chain inteira.

### Por que é perigoso

`children[0]` é uma suposição que só funciona quando a expressão do lado direito é resolvida com um único token. Para qualquer expressão multi-token (chain, binary expression, etc.), essa suposição falha silenciosamente — nenhum erro, output simplesmente errado.

### Proposta

**A. Solução implementada — usar `end($this->children)`**

`$this->node->right = !empty($this->children) ? end($this->children) : null` — o último filho adicionado é sempre a expressão mais recente resolvida. Implementado na feature 003.

**B. Modelar a expressão do lado direito explicitamente**

Uma solução mais robusta seria ter um `ExpressionContext` dedicado para o lado direito do assignment, que gerencia sua própria lista de nodes e retorna o nó raiz quando fechado. Isso separaria a responsabilidade do `AssignmentContext` de "gerenciar a declaração" da responsabilidade de "resolver a expressão RHS".

---

## 5. `FunctionNode.getRawType()` retornando `'Function'` ao invés do tipo de retorno

### O que aconteceu

`FunctionNode` implementa `Type` e tinha `getRawType()` hardcoded para retornar `'Function'`. Quando um `FunctionNode` se tornava o `variableOnFocus` (ex: após `replace(...)` em uma chain), o `FunctionCallResolver.isTheCase()` fazia `from(focus->type->getRawType())->getFunction(nextMethod)`. Como `focus->type` era o próprio `FunctionNode` e `getRawType()` retornava `'Function'`, o `SymbolTableManager` procurava em `FunctionMethods` — que não existe — e o lookup falhava.

### Por que é perigoso

Falha silenciosa: o `FunctionCallResolver.isTheCase()` retorna `false`, o token cai no `FunctionCallNotFoundResolver`, e a mensagem de erro diz "método não encontrado" — mas o método existe, o problema é que o tipo de lookup está errado. Isso leva o developer a procurar o problema nos TypeMethods quando o real problema é no tipo do nó em foco.

### Proposta

**A. Solução implementada — `getRawType()` dinâmico**

`FunctionNode.getRawType()` agora retorna `current($this->method->returnOfPhpExecution)` quando disponível. Implementado na feature 003.

**B. Separar `Type` de `Node` para FunctionNode**

`FunctionNode` implementa `Type` por conveniência, mas a semântica é ambígua: o nó em si não tem um tipo fixo, tem um **tipo de retorno** que depende do método. Uma solução mais clara seria um `FunctionResultType` wrapper que wraps o retorno do método e pode ser usado como `type` sem modificar o FunctionNode em si.

---

## 6. TypeErrors em `overrideVariableOnFocus` — tipagem estrita vs mixed

### O que aconteceu

`overrideVariableOnFocus()` fazia:
```php
$function->variableBase->value = $newVariable;  // TypeEror: VariableReferenceNode::$value espera VariableDeclarationNode
$function->variableBase->type = $newVariable;   // TypeError: FunctionNode::$type espera self
```

`VariableReferenceNode::$value` é declarado como `VariableDeclarationNode` (tipagem PHP 8 estrita). `FunctionNode::$type` é declarado como `self`. Tentar atribuir um `StringNode` ou `NumberNode` nesses campos lança `TypeError` em runtime — erro que antes não ocorria porque chains não funcionavam e esse código nunca era atingido.

### Por que é perigoso

A tipagem estrita é correta e desejável, mas o código que a viola estava "dormindo" atrás de um código que nunca executava. Conforme novas features são implementadas e mais caminhos do AST são percorridos, outros `TypeError` similares podem surgir em código que parecia estável.

### Proposta

**A. Auditoria de tipagem em Nodes**

Revisar todos os `Node` que têm campos públicos tipados com classes concretas (`VariableDeclarationNode`, `self`, etc.) e verificar se algum código em `Resolvers` ou `Checkers` tenta atribuir nesses campos com tipos incompatíveis. Isso pode ser feito com PHPStan level 9 sem supressões.

**B. `VariableReferenceNode::$value` aceitar qualquer `Node`**

O campo `value` em `VariableReferenceNode` sendo `VariableDeclarationNode` é muito restritivo. Em chains, o "value" pode ser um `FunctionNode` que é o elo anterior. Considerar ampliar para `Node` ou introduzir um campo separado `$chainBase` para não quebrar o contrato do campo existente.

**C. `FunctionNode::$type` como interface `Type` ao invés de `self`**

`FunctionNode::$type = $this` cria uma auto-referência que impede qualquer outra atribuição. Mudar para `public Type $type` permitiria que o campo receba qualquer node que implemente `Type`, alinhando com como os outros nodes funcionam.

---

## 7. Falta de uma ferramenta de inspeção de AST integrada

### O contexto geral

Os problemas 2, 3, 4 e 5 acima foram todos descobertos da mesma forma: criando scripts PHP externos manuais (`/tmp/test_parse.php`, `/tmp/test_emit.php`) que instanciavam manualmente o Parser, Binder e Emitter para inspecionar o estado intermediário do AST. Cada script levava 10-15 minutos para ser escrito e depurado por causa das dependências de construção dos managers.

### Proposta — `bin/inspect` como ferramenta de desenvolvimento

Criar um novo comando `bin/inspect` que aceita um arquivo `.ps` e mostra o estado do AST em cada fase do pipeline:

```bash
php phirescript/bin/inspect samples/success/case_42/StringChain.ps
```

Output:
```
=== TOKENS ===
T_IDENTIFIER [mystring] L1:1
T_SYMBOL [=] L1:9
T_STRING_LIT ['this is a string'] L1:11
...

=== AST (após Parse) ===
Program
  AssignmentNode L1
    left: VariableDeclarationNode [mystring]
    right: StringNode ['this is a string']
  AssignmentNode L2
    left: VariableDeclarationNode [processed]
    right: FunctionNode [length] returnType=Int
      variableBase: FunctionNode [replace] returnType=String
        variableBase: VariableReferenceNode [mystring] type=String

=== AFTER BIND ===
[diff: apenas mudanças em relação ao parse]

=== PRE-PHP (antes dos Processors) ===
$mystring = 'this is a string';
$processed = \strlen(\str_replace('is', 'is really', $mystring));

=== FINAL PHP ===
[output após PhpFileGeneratorHandler]
```

Com flags opcionais:
- `--phase=parse|bind|check|emit` para mostrar apenas uma fase
- `--diff` para mostrar só o que mudou entre fases
- `--json` para output em JSON (útil para tooling externo)

Isso eliminaria a necessidade de scripts externos manuais e reduziria o ciclo de debug de "30 minutos para escrever o script" para "1 comando".
