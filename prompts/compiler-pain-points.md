# PHireScript Compiler — Pain Points & Proposed Solutions

> Registrado após a implementação de method chaining (specs/003-method-chaining).
> Cada ponto descreve um problema real encontrado durante o desenvolvimento,
> com contexto técnico e proposta de solução ou melhoria.
> **Status**: Pontos 1 e 7 implementados em 2026-06-04. Ponto 9 implementado em 2026-06-06.

---

## 1. Cache silencioso mascarando bugs ✅ IMPLEMENTADO

### O que aconteceu

O compilador tem um `CacheManager` que persiste tokens, ASTs serializados e snapshots `.phc` em `.cache/`. Durante o desenvolvimento, um snapshot `.phc` antigo (com output errado) estava sendo copiado para `src/compiled/` pelo orchestrator, fazendo parecer que os fixes não tinham efeito. Rodadas do `bin/build` também retornavam o resultado antigo porque os tokens estavam em cache e o hash do arquivo ainda era considerado válido.

O pior: o compilador rodava sem erro e gerava output — mas o output era do cache, não do código corrigido.

### Por que é perigoso

Silêncio. Não há nenhuma indicação visual de que o resultado veio do cache. O developer (humano ou IA) debugga código que não está nem sendo executado.

### Implementado

**A. Banner visual de cache hit no modo `dev`** ✅

Quando um arquivo é compilado a partir do cache (tokens ou AST), emite uma linha `[CACHE HIT]` em STDERR no modo `dev: true`. Usa STDERR para não ser capturado pelo output buffer do orchestrator:

```
[CACHE HIT] StringChain.phs → tokens cached
[CACHE HIT] StringChain.phs → ast cached
```

**B. Flag `--no-cache` no `bin/build`** ✅

```bash
php phirescript/bin/build --no-cache
```

Passa `clean: true` para `CompilerContext`. O `Compiler.php` chama `$cache->flush()` antes de compilar.

**C. Invalidação automática por mtime do compiler** ✅

Na inicialização do `Compiler`, se qualquer `.php` em `phirescript/src/` for mais novo que o timestamp em `.cache/config/compiler_mtime.cache`, o cache é descartado automaticamente com aviso. O timestamp é gravado ao final de cada build bem-sucedido via `$cache->touchCompilerTimestamp()`.

**D. Invalidação automática por mudança no `PHireScript.json`** ✅

Adicionado junto ao item C: se o `PHireScript.json` mudou desde o último build (hash diferente no manifest), o cache inteiro é descartado com aviso. Usa `hasChangedSinceLastBuild()` no `CacheManager` para distinguir "arquivo novo" de "arquivo modificado".

**E. Cache sempre na raiz do pacote phirescript, não no cwd** ✅

`Compiler.php` usava `getcwd()` para determinar o diretório do `.cache/`, o que colocava a pasta dentro do projeto que chama o compilador (ex: sandbox), poluindo repos externos. Corrigido para `dirname(__DIR__)` — o cache fica sempre em `phirescript/.cache/`, independente do diretório de onde o comando é executado. Isso também garante isolamento correto quando o phirescript é instalado como dependência via Composer.

---

## 2. Debug invisível — output suprimido pelo output buffer

### O que aconteceu

O compilador usa `ob_start()` / `ob_get_clean()` internamente (em `SuccessMode`, `FileCompiler`, etc.) para capturar o output do compilador e alimentar `assertHasMessage`. Como consequência, qualquer `echo`, `print`, `var_dump`, ou `fwrite(STDERR)` inserido para debug dentro do pipeline de compilação é capturado e silenciado — nunca chega ao terminal.

Tentativas de usar `file_put_contents('/tmp/debug.log', ...)` dentro de classes do compilador também falharam porque o processo rodava em contexto diferente do esperado.

### Por que é perigoso

Impossibilita o ciclo clássico de debug "adicionar print → rodar → ver output". Leva o developer a criar scripts PHP externos complexos só para inspecionar o estado interno, aumentando muito o tempo de ciclo.

### Implementado parcialmente

**A. `Debug::dump()` que escreve em arquivo dedicado** ✅

Método `Debug::dump(mixed $value, string $label = '')` adicionado em `Helper/Debug/Debug.php`. Escreve em `/tmp/phirescript_debug.log` via `file_put_contents(..., FILE_APPEND)`. Não passa pelo output buffer. Pode ser chamado de qualquer ponto do pipeline:

```php
Debug::dump($parseContext->variables->getVariableOnFocus(), 'focus after dot');
// /tmp/phirescript_debug.log: [ThisPropertyAccessResolver.php:22] [focus after dot] VariableReferenceNode(mystring)
```

**B. `bin/debug` com saída de AST formatada** — ver item 7 (implementado via `bin/inspect`)

**C. `bin/debug` com saída de tokens** — ver item 7 (implementado via `bin/inspect --phase=tokens`)

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

## 7. Falta de uma ferramenta de inspeção de AST integrada ✅ IMPLEMENTADO

### O contexto geral

Os problemas 2, 3, 4 e 5 acima foram todos descobertos da mesma forma: criando scripts PHP externos manuais (`/tmp/test_parse.php`, `/tmp/test_emit.php`) que instanciavam manualmente o Parser, Binder e Emitter para inspecionar o estado intermediário do AST. Cada script levava 10-15 minutos para ser escrito e depurado por causa das dependências de construção dos managers.

### Implementado

**`bin/inspect` — ferramenta de inspeção de pipeline** ✅

```bash
# Todos os passos
php phirescript/bin/inspect samples/success/case_42/StringChain.phs

# Fase específica
php phirescript/bin/inspect <file.phs> --phase=tokens
php phirescript/bin/inspect <file.phs> --phase=parse
php phirescript/bin/inspect <file.phs> --phase=bind
php phirescript/bin/inspect <file.phs> --phase=emit

# Tokens pós-parse com a classe do resolver que processou cada um
php phirescript/bin/inspect <file.phs> --phase=processed
php phirescript/bin/inspect <file.phs> --processed   # shorthand

# JSON para tooling externo
php phirescript/bin/inspect <file.phs> --phase=tokens --json
```

**Flag `--processed` / `--phase=processed`** — inovação além da proposta original:

Mostra todos os tokens depois do parse, anotados com a classe do resolver que os processou (campo `Token::$processedBy`). Tokens com `(implicit)` não foram despachados por nenhum resolver (EOL consumido como fechamento de contexto, delimitadores, etc.). Isso permite inferir a lógica de construção do AST token a token:

```
T_KEYWORD       'this'        L8:9    ThisResolver
T_SYMBOL        '.'           L8:13   DotResolver
T_IDENTIFIER    'name'        L8:14   ThisPropertyAccessResolver
T_SYMBOL        '='           L8:19   (implicit)
T_STRING_LIT    '"default"'   L8:21   StringLiteralResolver
T_EOL           '\n'          L8:30   EndOfLineResolver
```

**Sem dependência de `PHireScript.json`** — usa config mínimo inline, funciona com qualquer arquivo `.phs` sem precisar apontar source/dist.

### O que poderia ser melhor

- **`--diff` entre fases**: mostrar apenas o que mudou no AST entre parse→bind (propriedades resolvidas, tipos inferidos). Útil para entender o que o Binder faz.
- **`--context-stack`**: mostrar o stack de contextos ativos a cada token (qual `AbstractContext` estava no topo quando o token foi processado). Hoje o `processedBy` diz *qual resolver* — mas não *em qual contexto* o resolver estava registrado.
- **Colorização por tipo de resolver**: agrupar visualmente resolvers por categoria (Declaration, Expression, Statement, Scope) com cores diferentes para identificar padrões de parsing.

---

## 8. Parâmetros de método não são registrados no `variables` scope

---

## 9. `BracketBalanceRule` contando `<`/`>` como colchetes ✅ IMPLEMENTADO

### O que aconteceu (descoberto em 2026-06-06, feature 005-inline-getter-setter)

O `BracketBalanceRule` do Validator contava `<` e `>` como um par de colchetes que precisa estar balanceado, usando `parenDepth === 0` para distinguir do uso dentro de generics como `List<String>`. Ao implementar a sintaxe `< Int id` (getter), o Validator rejeitava o arquivo com:

```
Amount of < (3) diverge from > (0)
```

Os marcadores de getter (`<`) e setter (`>`) são assimétricos por design — um property com `<` puro não tem `>` correspondente na mesma linha. O contrato de balanceamento não faz sentido para esse uso.

### Por que é perigoso

Bloqueia a feature inteiramente no Validator, antes mesmo de chegar ao Parser. O erro é enganoso: parece que o arquivo tem sintaxe inválida, mas na verdade é uma regra de validação que não distingue contextos de uso.

A dependência de `parenDepth` para distinguir generics de comparações era frágil: um `<` em comparação como `a < b` fora de parênteses seria contado como "abre generic" e esperaria um `>` para fechar.

### Implementado

**Remoção da checagem de `<`/`>` do `BracketBalanceRule`** ✅

`<` e `>` foram removidos dos arrays `$open` e `$close` do `BracketBalanceRule` e a lógica de `parenDepth` foi eliminada. PHireScript não usa `<`/`>` como delimitadores de escopo obrigatoriamente balanceados — generics são sketch e a validação estrutural de `{}`, `()`, `[]` já cobre os casos reais. O `ForbiddenTokenRule` continua cobrindo misuse do token `<>` combinado.

---

## 10. Operadores aritméticos não suportados em `ReturnContext`

### O que aconteceu (descoberto em 2026-06-06, feature 005-inline-getter-setter)

Ao tentar validar o override de getter com `return this.id * 2` no case_60, o compilador lança:

```
* is not supported return context!
```

O `ReturnContext` não tem um resolver para operadores aritméticos (`*`, `/`, `+`, `-`). O `*` é T_SYMBOL e não é reconhecido por nenhum resolver registrado no contexto.

### Por que é perigoso

Qualquer expressão aritmética básica em um return é inválida. `return this.count + 1`, `return price * 1.1`, `return n - 1` — todos falham. É uma lacuna silenciosa que não aparece como erro do compilador (o arquivo compila se o return não tiver aritmética), mas limita muito o que pode ser feito dentro de métodos.

### Proposta

**A. `BinaryExpressionResolver` em `ReturnContext`**

O `BinaryExpressionContext` já tem os operadores aritméticos e de comparação mapeados (`>`, `<`, `==`, etc.). O `ReturnContext` precisaria registrar um `BinaryExpressionResolver` que converte `expressão op expressão` em um `BinaryExpressionNode`, similar ao que acontece no `AssignmentContext`.

**B. Contexto de expressão genérico**

Uma solução mais robusta seria um `ExpressionContext` reutilizável que qualquer outro contexto pode usar para o RHS de uma expressão — `ReturnContext`, `AssignmentContext`, `IfConditionContext` — ao invés de cada um gerenciar seus próprios resolvers de expressão individualmente. Isso elimina a duplicação atual onde `ComparisonExpressionResolver` é registrado em 6+ contextos separados.

### O que aconteceu (descoberto em 2026-06-04, feature 004-this)

Durante a implementação da feature `this`, ao tentar escrever `this.prop = paramName` dentro de um método de classe, o compilador lança `CompileException: paramName is not supported in assignment context!`. Parâmetros de métodos de classe (ex: `setName(String n)`) não são adicionados ao `VariableManager` do scope do método — eles existem apenas no nó AST `ParamsListNode`.

O `VariableReferenceResolver.isTheCase()` verifica `$parseContext->variables->getVariable($token->value)` — se o parâmetro não está no `variables`, retorna `false` e o token cai no `FunctionCallNotFoundResolver` ou lança exception.

### Por que é perigoso

Impede qualquer lógica útil de método com parâmetros: `this.name = newName` onde `newName` é um parâmetro é o padrão mais básico de setter. Força casos de sandbox a usar apenas literais no RHS de assignments com `this.prop`.

### Proposta

No `MethodScopeResolver.resolve()` (ou no contexto que abre o method scope), após `enterScope()`, iterar sobre os parâmetros do método pai e chamara `$parseContext->variables->addVariable(new VariableDeclarationNode(...))` para cada um. O `ArrowFunctionDeclarationContext` já faz isso para arrow functions (linha 70 do arquivo) — o mesmo padrão deve ser aplicado para métodos de classe.
