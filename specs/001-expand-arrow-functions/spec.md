# Especificação de Feature: Expansão de Arrow Functions

**Feature Branch**: `001-expand-arrow-functions`

**Criado em**: 2026-05-25

**Status**: Rascunho

---

## Visão Geral

Arrow functions no PHireScript são funções anônimas de primeira classe, análogas às arrow functions do JavaScript/TypeScript. Elas permitem declarar lógica inline de forma concisa, com parâmetros tipados e corpo delimitado por `{}`. Atualmente a funcionalidade existe parcialmente no compilador — o caso de uso básico compila, mas há limitações e bugs em cenários com múltiplos parâmetros e valores default nos parâmetros.

> **Nota sobre `use` e `external`**: No PHireScript, `use` é exclusivamente a instrução de importação de pacotes do projeto (análogo ao `import` do TypeScript). `external` é a forma de declarar dependência de uma biblioteca PHP já pronta. O `use ($variavel)` que o PHP exige em closures para capturar variáveis do escopo externo **não existe na sintaxe PHireScript** — é um detalhe de implementação gerado automaticamente pelo compilador, invisível para quem escreve `.phs`.

### Sintaxe PHireScript

```
// Zero parâmetros
calcular = (): Float => {
    return 3.14
}

// Um parâmetro tipado
dobrar = (Int numero): Int => {
    return numero * 2
}

// Múltiplos parâmetros tipados
calcTotal = (Float preco, Float taxa): Float => {
    return preco * (1 + taxa)
}

// Parâmetro com valor default
saudar = (String nome = "mundo"): String => {
    return "Olá, " + nome
}

// Arrow function passada como argumento
processar = (Function callback): Void => {
    callback()
}
```

### Saída PHP esperada

Dependendo do contexto, a arrow function compila para dois formatos PHP:

**Formato 1 — Função anônima simples** (quando não há referência a variáveis do escopo externo):
```php
// PHireScript: calcTotal = (Float preco, Float taxa): Float => { return $preco * (1 + $taxa) }
$calcTotal = function(float $preco, float $taxa): float {
    return $preco * (1 + $taxa);
};
```

**Formato 2 — Função anônima com `use` gerado automaticamente** (quando o compilador detecta referências a variáveis do escopo externo no corpo):
```php
// PHireScript (.phs) — sem nenhuma instrução use especial:
//   aplicarDesconto = (Float preco): Float => { return preco * desconto }
//
// PHP gerado pelo compilador (use ($desconto) inserido automaticamente):
$aplicarDesconto = function(float $preco) use ($desconto): float {
    return $preco * $desconto;
};
```

---

## Cenários de Uso e Teste

### Cenário 1 — Arrow function sem parâmetros (Prioridade: P1)

Um desenvolvedor declara uma arrow function que não recebe argumentos e retorna um valor fixo ou baseado em variáveis capturadas.

**Por que esta prioridade**: É o caso mais simples e serve como base para todos os outros. Valida que o compilador consegue produzir uma função anônima PHP sem lista de parâmetros.

**Teste independente**: Pode ser validado com um único caso de compilação que declara `obterPi = (): Float => { return 3.14 }` e verifica que o PHP gerado é `$obterPi = function(): float { return 3.14; };`.

**Cenários de Aceitação**:

1. **Dado** um arquivo `.phs` com `obterPi = (): Float => { return 3.14 }`, **Quando** compilado, **Então** o PHP gerado deve conter `$obterPi = function(): float {` sem parâmetros na lista.
2. **Dado** uma arrow function sem parâmetros com tipo de retorno `Void`, **Quando** compilada, **Então** deve gerar `function(): void {`.
3. **Dado** uma arrow function sem parâmetros que captura uma variável externa `$base`, **Quando** compilada, **Então** deve gerar `function() use ($base)`.

---

### Cenário 2 — Arrow function com um parâmetro tipado (Prioridade: P1)

Um desenvolvedor declara uma arrow function com um único parâmetro obrigatório e tipado.

**Por que esta prioridade**: É o caso de uso mais comum. Valida a leitura de tipo + nome do parâmetro e a emissão com type hint PHP.

**Teste independente**: Um caso de compilação com `dobrar = (Int numero): Int => { return numero * 2 }` que produz `$dobrar = function(int $numero): int { return $numero * 2; };`.

**Cenários de Aceitação**:

1. **Dado** `dobrar = (Int numero): Int => { return numero * 2 }`, **Quando** compilado, **Então** gera `function(int $numero): int`.
2. **Dado** um parâmetro do tipo `String`, **Quando** compilado, **Então** gera `string $nome` com type hint em minúsculas.
3. **Dado** um parâmetro do tipo super tipo (`Email`), **Quando** compilado, **Então** mantém o type hint da classe correspondente do super tipo.
4. **Dado** uma arrow function com parâmetro mas sem tipo declarado, **Quando** compilada, **Então** o compilador deve lançar um erro de compilação informando que o parâmetro precisa ser tipado.

---

### Cenário 3 — Arrow function com múltiplos parâmetros (Prioridade: P1)

Um desenvolvedor declara uma arrow function com dois ou mais parâmetros tipados, separados por vírgula.

**Por que esta prioridade**: Este é atualmente o cenário com bugs conhecidos. Múltiplos parâmetros precisam ser parseados corretamente e emitidos com vírgulas na lista PHP.

**Teste independente**: Um caso com `calcTotal = (Float preco, Float taxa): Float => { return preco * (1 + taxa) }` que valida a lista de parâmetros completa no PHP gerado.

**Cenários de Aceitação**:

1. **Dado** `calcTotal = (Float preco, Float taxa): Float => { return preco * (1 + taxa) }`, **Quando** compilado, **Então** gera `function(float $preco, float $taxa): float`.
2. **Dado** três parâmetros de tipos diferentes, **Quando** compilado, **Então** todos aparecem na lista separados por `, ` sem parâmetro duplicado ou faltando.
3. **Dado** dois parâmetros com o mesmo tipo, **Quando** compilado, **Então** cada um aparece como uma entrada distinta na lista.

---

### Cenário 4 — Parâmetro com valor default (Prioridade: P2)

Um desenvolvedor declara uma arrow function onde um ou mais parâmetros possuem valor padrão.

**Por que esta prioridade**: Necessário para APIs internas onde alguns argumentos são opcionais. Depende do Cenário 2 e 3 funcionarem primeiro.

**Teste independente**: Um caso com `saudar = (String nome = "mundo"): String => { return "Olá, " + nome }` que valida a emissão `function(string $nome = "mundo"): string`.

**Cenários de Aceitação**:

1. **Dado** `saudar = (String nome = "mundo"): String => { ... }`, **Quando** compilado, **Então** gera `function(string $nome = "mundo"): string`.
2. **Dado** um default de valor numérico inteiro `Int taxa = 0`, **Quando** compilado, **Então** gera `int $taxa = 0`.
3. **Dado** um default `null`, **Quando** compilado, **Então** gera `?string $nome = null` (tipo anulável).
4. **Dado** múltiplos parâmetros onde apenas o último tem default, **Quando** compilado, **Então** os sem default vêm primeiro, o com default por último.
5. **Dado** um parâmetro com default antes de um sem default, **Quando** compilado, **Então** o compilador deve emitir um erro informando que parâmetros opcionais devem vir após os obrigatórios.

---

### Cenário 5 — Arrow function que referencia variáveis do escopo externo (Prioridade: P2)

Quando uma arrow function usa variáveis declaradas no escopo onde ela está inserida, o compilador precisa detectar essas referências e gerar automaticamente a cláusula `use (...)` no PHP. O desenvolvedor PHireScript **não escreve nada diferente** — o código `.phs` é idêntico ao de uma arrow function que não captura nada; é o compilador que resolve a necessidade de captura.

**Por que esta prioridade**: Fundamental para o uso real de arrow functions dentro de métodos de classe ou funções, onde o acesso a variáveis locais do escopo externo é comum. Sem isso, referenciar `desconto` dentro da arrow function geraria PHP inválido (variável indefinida).

**Teste independente**: Um caso onde `desconto = 0.1` é declarado antes da arrow function e referenciado dentro do corpo dela. O PHP gerado deve incluir `use ($desconto)` — sem nenhuma instrução `use` no código `.phs`.

**Cenários de Aceitação**:

1. **Dado** `desconto = 0.1` declarado antes, e `aplicar = (Float preco): Float => { return preco * desconto }` em `.phs`, **Quando** compilado, **Então** gera `function(float $preco) use ($desconto): float` — sem qualquer `use` na sintaxe PHireScript.
2. **Dado** múltiplas variáveis do escopo externo referenciadas no corpo, **Quando** compilado, **Então** todas aparecem na cláusula `use` do PHP gerado, separadas por vírgula.
3. **Dado** uma arrow function que não referencia nenhuma variável do escopo externo, **Quando** compilada, **Então** nenhuma cláusula `use` é emitida no PHP.
4. **Dado** uma arrow function dentro de um método de classe que referencia `this`, **Quando** compilada, **Então** `$this` é incluído automaticamente na cláusula `use` do PHP — sem sintaxe especial no `.phs`.

---

### Cenário 6 — Arrow functions aninhadas (Prioridade: P3)

Um desenvolvedor declara uma arrow function dentro do corpo de outra arrow function, análogo a funções aninhadas em TypeScript.

**Por que esta prioridade**: Caso de uso avançado, depende de todos os cenários anteriores. O compilador deve emitir funções anônimas PHP aninhadas corretamente, com captura automática de variáveis em cada nível.

**Teste independente**: Um caso onde a arrow function interna referencia um parâmetro da externa e uma variável do escopo externo, validando que o PHP gerado possui dois níveis de `function(...) use (...) { ... }`.

**Cenários de Aceitação**:

1. **Dado** uma arrow function aninhada que referencia apenas seus próprios parâmetros, **Quando** compilada, **Então** gera uma função anônima PHP aninhada sem cláusula `use` interna.
2. **Dado** a arrow function interna referenciando um parâmetro da externa, **Quando** compilada, **Então** o compilador gera `use ($paramExterno)` na função anônima interna.

---

### Cenário 7 — Arrow function como argumento de outra função (Prioridade: P3)

Um desenvolvedor passa uma arrow function diretamente como argumento em uma chamada de função.

**Por que esta prioridade**: Caso de uso avançado que depende de todos os cenários anteriores. Expande o poder expressivo da linguagem mas é menos urgente.

**Teste independente**: Um caso onde `array_map((Int n): Int => { return n * 2 }, numeros)` é compilado validando que a arrow function inline é emitida corretamente como argumento.

**Cenários de Aceitação**:

1. **Dado** uma arrow function passada diretamente como argumento, **Quando** compilado, **Então** o PHP gerado usa `function(...) { ... }` inline dentro da chamada.
2. **Dado** uma arrow function sem parâmetros como argumento, **Quando** compilado, **Então** gera `function() { ... }` sem lista de parâmetros.

---

### Casos de Borda

- **Corpo vazio sem `Void`** — `somar = (Int a, Int b): Int => {}` deve gerar erro no checker: corpo vazio só é válido quando o tipo de retorno é `Void` (ex: `(): Void => {}`).

- **Conflito entre tipo de retorno e `return`** — o checker deve lançar erro explicativo em dois casos: (a) tipo de retorno declarado como `Void` mas o corpo contém `return valor`; (b) tipo de retorno não-`Void` mas o corpo não possui `return`.

- **Tipo de retorno ausente** — o `: TipoRetorno` faz parte do padrão sintático reconhecido pelo parser (`(params): Tipo => {}`); sem ele o parser não identifica o construct como uma arrow function e lança um erro de parse, antes mesmo de chegar ao checker.

- **Reatribuição de variável** — arrow functions são valores atribuídos a variáveis, como em JavaScript/TypeScript; reatribuir uma variável existente (`desconto = 0.1` seguido de `desconto = (Float x): Float => { ... }`) é válido e sobrescreve o valor anterior sem erro.

- **Parâmetro com tipo union** — `String|Int` é suportado e compila para `string|int $parametro` no PHP, seguindo a mesma convenção de union types do resto da linguagem.

- **Arrow functions aninhadas** — suportadas, análogo a funções aninhadas em TypeScript; compilam para funções anônimas PHP aninhadas. A arrow function interna pode capturar variáveis da arrow function externa (o compilador gera `use (...)` automaticamente nos dois níveis).

---

## Requisitos

### Requisitos Funcionais

- **RF-001**: O compilador DEVE aceitar arrow functions com zero parâmetros, na forma `nome = (): TipoRetorno => { corpo }`.
- **RF-002**: O compilador DEVE aceitar arrow functions com um ou mais parâmetros tipados; tanto os tipos dos parâmetros quanto o tipo de retorno admitem union types — ex: `(Tipo|String param1, Tipo|Int param2): TipoRetorno|Int|String => { corpo }`.
- **RF-003**: Todo parâmetro de uma arrow function DEVE ter um tipo declarado explicitamente; a ausência de tipo deve resultar em erro de compilação.
- **RF-004**: O compilador DEVE aceitar valores default nos parâmetros para: literais primitivos (string, inteiro, float, booleano, null), SuperTypes (Email, Uuid, Ipv4, etc.), MetaTypes, e objetos de tipos importados via `use` ou `external`.
- **RF-005**: Parâmetros com valor default DEVEM ser posicionados após os parâmetros obrigatórios; violações devem resultar em erro de compilação.
- **RF-006**: O compilador DEVE emitir uma função anônima PHP (`function(...) { ... }`) para arrow functions com corpo de múltiplas linhas.
- **RF-007**: O compilador DEVE detectar automaticamente quais variáveis do escopo externo são referenciadas dentro do corpo da arrow function e incluir a cláusula `use (...)` no PHP gerado — sem nenhuma instrução adicional na sintaxe `.phs`.
- **RF-008**: O compilador NÃO DEVE incluir a cláusula `use` quando a arrow function não referenciar nenhuma variável do escopo externo.
- **RF-009-b**: A instrução `use` do PHireScript (importação de pacotes) e a cláusula `use ($var)` do PHP (captura de closure) são conceitos distintos e não devem se misturar — o desenvolvedor PHireScript nunca escreve `use ($var)` manualmente.
- **RF-009**: Os tipos PHireScript dos parâmetros DEVEM ser convertidos para os type hints PHP correspondentes (`String` → `string`, `Int` → `int`, `Float` → `float`, `Bool` → `bool`); tipos union como `String|Int` devem compilar para `string|int`.
- **RF-010**: O tipo de retorno é parte obrigatória da sintaxe; a ausência do `: TipoRetorno` impede o parser de reconhecer o construct como arrow function, resultando em erro de parse (não de checker).
- **RF-011**: O tipo de retorno DEVE ser emitido como return type PHP (e.g., `: float`).
- **RF-012**: O checker DEVE rejeitar um corpo vazio quando o tipo de retorno não for `Void`.
- **RF-013**: O checker DEVE rejeitar um `return valor` dentro de uma arrow function declarada com tipo de retorno `Void`, e vice-versa (tipo não-`Void` sem `return` no corpo).

### Entidades-chave

- **ArrowFunctionNode**: Nó AST que representa uma arrow function; contém `parameters` (ParamsListNode), `returnType` (ReturnTypeNode) e `bodyCode` (MethodScopeNode).
- **ParamArgumentNode**: Nó AST de um parâmetro individual; contém `types[]`, `name` e `value` (default).
- **ArrowFunctionDeclarationContext**: Contexto de parsing que processa os tokens após o identificador da arrow function.
- **ArrowFunctionEmitter**: Emissor que converte `ArrowFunctionNode` em código PHP.

---

## Critérios de Sucesso

### Resultados Mensuráveis

- **CS-001**: Todos os casos de compilação dos Cenários 1, 2 e 3 passam sem erros após a implementação.
- **CS-002**: O caso de compilação existente em `samples/feature/case_12` compila sem erros; o PHP gerado deve ser validado contra o comportamento esperado definido nesta spec (o `.phc` existente é apenas referência histórica e pode estar incorreto).
- **CS-003**: Cada cenário desta spec possui ao menos um caso de integração (`CaseValidation.php`) no sandbox com asserções verificando o PHP gerado.
- **CS-004**: Nenhuma regressão é introduzida nos 34 casos de sucesso existentes — `php bin/stretch --mode=success` continua passando 100%.
- **CS-005**: Os testes unitários do compilador (`vendor/bin/phpunit` dentro de `phirescript/`) continuam passando sem falhas.
- **CS-006**: O código novo passa na análise estática do PHPStan nível 9 sem supressões.

---

## Premissas

- O tipo de retorno é parte obrigatória da sintaxe reconhecida pelo parser; inferência de tipo de retorno está fora de escopo. A ausência do `: TipoRetorno` gera erro de parse, não de checker.
- No PHireScript, `use` é exclusivamente importação de pacotes e `external` é para bibliotecas PHP prontas; a cláusula `use ($var)` de closures PHP é sempre gerada pelo compilador, nunca escrita pelo desenvolvedor.
- A detecção de variáveis capturadas (Cenário 5) pode ser implementada como análise simples de referências no corpo da arrow function em relação ao escopo local do arquivo — análise de fluxo completa está fora de escopo.
- Arrow functions aninhadas (uma dentro de outra) estão fora de escopo desta feature.
- A feature se limita ao contexto de atribuição (`nome = (...) => { ... }`); arrow functions como argumentos inline (Cenário 6) são consideradas bônus e podem ser implementadas como extensão futura.
- O compilador já possui infraestrutura funcional para parsing de parâmetros (`ParameterListContext`, `ParameterArgumentContext`, `ParamArgumentNode`) que será reutilizada; não é necessário criar novos contextos para os parâmetros.
- O `IgnoreArrowResolver` (que descarta o token `=>` durante o parsing) está correto e não precisa de mudança.
