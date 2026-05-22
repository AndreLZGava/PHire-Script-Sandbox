# Method Chaining — Broken Feature Analysis

> Levantamento dos cenários quebrados na feature de method chaining em PHireScript.
> Compilações feitas em `samples/feature/` com `php phirescript/bin/build <case> src/compiled extra`.

---

## O que existe hoje (infraestrutura)

### Runtime — métodos já definidos
Os métodos dos tipos existem e estão bem definidos em:
- `phirescript/src/Runtime/DefaultOverrideMethods/Types/` — Array, Bool, Float, Int, String, List, Map, Object, Queue, Stack
- `phirescript/src/Runtime/DefaultOverrideMethods/SuperTypes/` — Email, Uuid, Color, Url, Cron, Duration, Json, Mac, Ipv4, Ipv6, Slug

Cada método retorna um `BaseMethods` com:
- `name` — nome do método PHireScript
- `phpCodeForConversion` — template PHP gerado
- `returnOfPhpExecution` — tipo(s) retornados (define o foco do próximo elo na chain)
- `subTypes` — tipos internos do container (para Array<String>)
- `params` — parâmetros esperados
- `overridesSelfParam` — se true, a variável em foco muda para o tipo retornado (suporte a chain)

### Parser — o que já resolve
- `FunctionCallResolver` — resolve uma chamada de método quando a variável em foco é encontrada no SymbolTable
- `FunctionCallNotFoundResolver` — fallback que **lança exceção** quando FunctionCallResolver não encontra o método
- `DotResolver` (Statements) — reconhece `.` mas não faz nada (`resolve()` está vazio)
- `DotResolver` (Root) — reconhece `.` e faz `addChild($token->value)` — sem lógica real

### O que parcialmente funciona (success/case_13)
```
variables = ['test': 'value']
variables.add('key', 'val').add(['k': 0], 'v').destroy!('key')
newVarBool = variables.contains?('another')
```
Esses passam — mas o array foi criado como literal `[...]` e o tipo Array é inferido corretamente.

---

## Cenários Quebrados

### Cenário 1 — Método standalone em variável String
**Arquivo:** `samples/feature/case_10/ArrayMethods.ps`

```
myString = 'day month Year'
myString.split(' ')
```

**Erro:**
```
CompileException: This method "split" does not exist nor is supported for this type of variable
Stack: FunctionCallNotFoundResolver->resolve() ← ProgramContext.php:104
```

**Causa raiz:** `FunctionCallResolver.isTheCase()` checa `getVariableOnFocus()?->type?->getRawType()`. Após o token `.`, o `DotResolver` em `Statements/` chama `resolve()` vazio — não sinaliza ao contexto qual variável está em foco. Quando `split` chega, o foco está null e o SymbolTable lookup falha, caindo no `FunctionCallNotFoundResolver`.

O método `split` **existe** em `StringMethods` — o problema é de foco, não de definição.

---

### Cenário 2 — Resultado de método atribuído a variável
**Arquivo:** `samples/feature/case_18/VariablesString.ps`

```
myVariable = variables.join(' ', 'meu teste')
```

**Erro:**
```
CompileException: This method "join" does not exist nor is supported for this type of variable
Stack: FunctionCallNotFoundResolver->resolve() ← AssignmentContext.php:98
```

**Causa raiz:** O `AssignmentContext` também tem `FunctionCallResolver`, mas o mesmo problema de foco se aplica: ao processar o lado direito da atribuição, a variável `variables` não está sendo trackada como "em foco" quando o `.` e depois `join` são encontrados.

O método `join` **existe** em `StringMethods`.

---

### Cenário 3 — Encadeamento cruzando tipos (Array → Bool)
**Arquivo:** `samples/feature/case_10/ArrayMethods.ps` (comentado), `success/case_13`

```
// Intenção:
result = myArray.contains?('item')   // returns Bool
// then:
result.not()                          // Bool method
// or inline:
myArray.contains?('item').not()
```

**Problema de design (não só parser):**
Quando `contains?` retorna `Bool`, o mecanismo `overrideVariableFocus` em `FunctionCallResolver` deveria criar um nó virtual de tipo `Bool` e torná-lo o novo foco para o próximo `.`. Isso **existe** no código mas depende do Cenário 1 funcionar primeiro (o foco precisa estar correto antes de poder ser sobrescrito).

O `getNewVirtualVariable()` em `FunctionCallResolver` só suporta: `Array`, `String`, `Int`, `Float`, `Object`, `Bool`. Se o tipo retornado for `Null` ou um SuperType, lança exceção (`'Type not supported'`).

---

### Cenário 4 — Encadeamento em múltiplas linhas
**Arquivo:** `samples/feature/case_10/ArrayMethods.ps`

```
myString.split(' ')
  .add('hour')
```

**Erro:** O EOL entre `.split(' ')` e `.add('hour')` quebra o parse. O parser processa a EOL como fim de statement, encerra o contexto da expressão, e o `.add('hour')` na linha seguinte não tem contexto de variável em foco.

**Design gap:** Multi-line chaining requer que EOL dentro de uma chain seja tratado como whitespace, não como terminador de statement. Precisa de um sinal — como o `.` no início da linha nova (Ruby/TypeScript style) — para indicar continuação.

---

### Cenário 5 — Chamada de método no construtor externo
**Arquivo:** `samples/feature/case_13/External.ps`

```
date = DateTime().modify('+3 days')
  .modify('+2 hours')
  .format('d/m/Y H:i')
```

**Erro:**
```
CompileException: ( is not supported in assignment context!
Stack: AssignmentContext.php
```

**Causa raiz:** `DateTime()` — chamada de construtor de classe `external` dentro de `AssignmentContext` — o token `(` após o nome de uma classe não está mapeado nesse contexto. O `AssignmentContext` não possui um resolver para instanciar classes externas.

Dois problemas separados:
1. Instanciação de classe externa em atribuição (`DateTime()`)
2. Encadeamento sobre o resultado da instanciação (`.modify(...)`)

---

### Cenário 6 — Typed collections (Map, Queue, Stack, List)
**Arquivo:** `samples/feature/case_17/Queue.ps`, `case_16/Map.ps`, `case_14/Comment.ps`

```
myQueue = Queue<String>
myQueue.enqueue!('string 1', 'string 2')
```

**Erro:**
```
CompileException: This method "enqueue!" does not exist nor is supported for this type of variable
Stack: FunctionCallNotFoundResolver->resolve() ← ProgramContext.php:104
```

**Causa raiz (duas camadas):**
1. `Queue<String>` como expressão de atribuição não é suportado — o parser não reconhece a sintaxe genérica `Type<SubType>` fora de declarações de método
2. Mesmo que `myQueue` fosse criado, `enqueue!` não seria encontrado porque o foco não seria setado corretamente (mesmo problema do Cenário 1)

Os métodos `enqueue!`, `dequeue!` **existem** em `QueueMethods`, assim como `Map`, `Stack`, `List` têm seus `*Methods`. O problema é puramente de parsing.

---

### Cenário 7 — Arrow functions emitem PHP inválido
**Arquivo:** `samples/feature/case_12/ArrowFunctions.ps`

```
calcTotal = (Float price, Float rate): Float => {
    return 12
}
```

**PHP gerado (inválido):**
```php
$calcTotal = function (price $, rate $): float {
    return 12;
};
```

**Erro:** `Syntax error, unexpected '$', expecting T_VARIABLE on line 4`

**Causa raiz:** O emitter inverte tipo e nome do parâmetro. Gera `price $float` ao invés de `float $price`. Bug no `ArrowFunctionResolver/Emitter` na ordem de serialização dos parâmetros.

---

## Mapa de Dependências entre Cenários

```
Cenário 1 (foco de variável após dot)
  └─ bloqueia → Cenário 2 (assign de resultado de método)
  └─ bloqueia → Cenário 3 (chain cruzando tipos)
  └─ bloqueia → Cenário 4 (multi-line chain, precisa de foco correto antes)

Cenário 4 (EOL dentro de chain)
  └─ depende de → Cenário 1 (foco correto)

Cenário 5 (external constructor + chain)
  └─ independente (mas se beneficia de Cenário 1 para o chain após instanciação)

Cenário 6 (typed collections)
  └─ depende de → Cenário 1 (foco após dot)
  └─ depende de → novo suporte a `Type<SubType>` no parser

Cenário 7 (arrow functions)
  └─ independente — bug isolado no emitter
```

---

## Bloqueador Central

**Todos os cenários de method chaining dependem de um único mecanismo quebrado:**

> Após o parser processar `varName.`, a variável `varName` precisa ser marcada como "em foco" para que o próximo token (o nome do método) seja buscado no SymbolTable com o tipo correto.

O `DotResolver` (Statements) hoje faz `resolve() { }` — completamente vazio. Ele reconhece o `.` mas não sinaliza nada. Isso é o bloqueador raiz.

---

## Superfície de Casos de Uso (o que a feature precisa cobrir)

```
// 1. Statement simples (sem atribuição)
myVar.methodName(args)

// 2. Atribuição do resultado
result = myVar.methodName(args)

// 3. Chain inline (mesmo tipo ou tipo diferente)
result = myVar.method1(args).method2(args)

// 4. Chain multi-linha (continuação explícita com . no início)
result = myVar
  .method1(args)
  .method2(args)

// 5. Chain sobre resultado de construtor
result = TypeName(args).method(args)

// 6. Chain sobre resultado de método de tipo diferente
found = myArray.contains?('item')   // Bool
msg = found.toString()               // String — foco muda de Array para Bool

// 7. Typed collections como variáveis
myQueue = Queue<String>
myQueue.enqueue!('a')
newItem = myQueue.dequeue!()
```

---

## Sugestões de Redesign

### Sugestão A — Corrigir o bloqueador raiz (DotResolver + foco)
O `DotResolver` (Statements e Root) precisa, ao ver `.`, recuperar a última expressão do contexto (o nó da variável ou função anterior) e registrá-la como "variável em foco" no `variablesManager`. Isso desbloquearia os cenários 1, 2 e 3.

### Sugestão B — Chain multi-linha (decisão tomada)

**Regra confirmada:** quando o parser encontra `.` e o token anterior é um **EOL** (quebra de linha) ou um **identifier**, trata como continuação de chain — não como novo statement.

```
// Inline — token anterior ao . é identifier ou )
result = myString.split(' ').add('hour')

// Multi-linha — token anterior ao . é EOL → continuação
result = myString.split(' ')
  .add('hour')
  .toLowerCase()

// Também válido partindo do nome da variável
result = myString
  .split(' ')
  .add('hour')
```

**Impacto na implementação:** o `DotResolver` precisa, ao encontrar `.`, verificar o token anterior via `getPreviousToken()`. Se for EOL, sinaliza continuação de chain mantendo o foco atual. A EOL antes do `.` não pode ser tratada como terminador de statement nesses casos.

### Sugestão C — Typed collections: decidir a sintaxe
Hoje os samples usam `myQueue = Queue<String>` mas isso nunca foi implementado no parser. Opções:

| Opção | Sintaxe PHireScript | PHP gerado |
|---|---|---|
| Atual (genérico com `<>`) | `myQueue = Queue<String>` | `$myQueue = new Queue(String::class)` |
| Construtor explícito | `myQueue = Queue(String)` | `$myQueue = new Queue(String::class)` |
| Keyword `of` | `myQueue = Queue of String` | `$myQueue = new Queue(String::class)` |

A opção `Queue<String>` é a mais AI-friendly (TypeScript/Java style) mas cria ambiguidade com o parser de comparações (`<` e `>` já são operadores). **Essa é uma decisão de design que precisa ser tomada antes da implementação.**

### Sugestão D — `getNewVirtualVariable` incompleto
Quando `overrideVariableFocus` cria um nó virtual para o próximo elo da chain, só suporta `Array`, `String`, `Int`, `Float`, `Object`, `Bool`. SuperTypes (Email, Uuid, etc.) lançam exceção. Precisará de extensão quando a chain cruzar tipos menos comuns.

### Sugestão E — Arrow functions (bug isolado)
O emitter de arrow functions inverte `Type name` para `name $Type`. Fix cirúrgico no emitter — independente de todo o resto.

---

## Ordem de Implementação Sugerida

1. **Cenário 7** — Arrow function emitter (bug isolado, baixo risco, desbloqueia o case_12)
2. **Cenário 1** — Foco após dot em ProgramContext (bloqueador raiz de tudo)
3. **Cenário 2** — Foco após dot em AssignmentContext (mesma correção, contexto diferente)
4. **Cenário 3** — Validar que `overrideVariableFocus` funciona corretamente após 1 e 2
5. **Cenário 4** — Multi-line chain (decisão de sintaxe primeiro)
6. **Cenário 6** — Typed collections (decisão de sintaxe `<>` vs construtor primeiro)
7. **Cenário 5** — External constructor + chain (depende de 1, 2, e suporte a `external` em AssignmentContext)
