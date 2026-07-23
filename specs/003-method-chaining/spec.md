# Feature Specification: Method Chaining

**Feature Branch**: `003-method-chaining`

**Created**: 2026-06-03

**Status**: Draft

**Input**: Method chaining para tipos primitivos e supertypes no PHireScript — encadeamento de chamadas de método sobre variáveis e literais com garantia de previsibilidade de tipo em cada elo.

---

## Contexto

O PHP expõe milhares de funções nativas sem padronização de assinatura (ordem de parâmetros, retorno, uso de referência). O PHireScript resolve isso com uma camada de métodos por tipo (`StringMethods`, `ArrayMethods`, `BoolMethods`, etc.) onde cada método encapsula uma função PHP e expõe uma interface previsível.

O objetivo desta feature é permitir que o desenvolvedor **encadeie chamadas de método diretamente sobre variáveis e literais**, com o compilador verificando em tempo de compilação que cada elo da chain é válido para o tipo retornado pelo elo anterior.

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Chain básica com atribuição (Priority: P1)

O desenvolvedor declara uma variável e encadeia métodos sobre ela, atribuindo o resultado a uma nova variável. A variável de origem permanece inalterada.

**Why this priority**: É o caso de uso mais comum e o bloqueador raiz de todos os outros cenários. Sem isso nada mais funciona.

**Independent Test**: Pode ser testado compilando um arquivo `.phs` com `mystring.replace(...).length()` atribuído a uma variável e verificando que o PHP gerado é `strlen(str_replace(..., $mystring))` com `$mystring` inalterado.

**Acceptance Scenarios**:

1. **Given** uma variável `mystring = 'this is a string'`, **When** o desenvolvedor escreve `processed = mystring.replace('is', 'is really').length()`, **Then** o PHP gerado é `$processed = strlen(str_replace('is', 'is really', $mystring))` e `$mystring` permanece inalterado.
2. **Given** uma chain de três métodos todos retornando `String`, **When** compilado, **Then** cada chamada é aninhada corretamente na ordem da chain (inner-to-outer).
3. **Given** uma chain onde o primeiro método retorna `String` e o segundo `Int`, **When** compilado, **Then** o tipo da variável de destino é inferido como `Int`.

---

### User Story 2 — Auto-atribuição explícita (Priority: P1)

O desenvolvedor quer sobrescrever a variável de origem com o resultado da chain. O PHireScript exige que isso seja explícito — `mystring = mystring.method()` — sem inferência implícita de mutação.

**Why this priority**: Garante que não haja mutação silenciosa de variáveis. Regra de design fundamental para previsibilidade.

**Independent Test**: Compilar `mystring = mystring.toUpperCase().replace('A','B')` e verificar que `$mystring` é reatribuído e o tipo é atualizado no SymbolTable.

**Acceptance Scenarios**:

1. **Given** `mystring = 'hello'`, **When** o desenvolvedor escreve `mystring = mystring.toUpperCase()`, **Then** `$mystring = mb_strtoupper($mystring, 'UTF-8')` e o tipo de `mystring` no SymbolTable permanece `String`.
2. **Given** `mystring = 'hello'`, **When** o desenvolvedor escreve `mystring = mystring.length()`, **Then** `$mystring = strlen($mystring)` e o tipo de `mystring` é atualizado para `Int`.
3. **Given** uma chain sem atribuição explícita como `mystring.toUpperCase()` em statement isolado, **When** compilado, **Then** o Checker lança erro indicando que o resultado é descartado (ver Regra 4 — Dead chain).

---

### User Story 3 — Chain em contexto de expressão (Priority: P2)

O desenvolvedor usa uma chain como expressão dentro de um `if` ou como argumento de outro método. O resultado alimenta o contexto pai sem precisar de variável intermediária.

**Why this priority**: Habilita uso fluente dentro de estruturas de controle, reduzindo variáveis temporárias desnecessárias.

**Independent Test**: Compilar `if(mystring.length() > 5)` e verificar que o PHP gerado é `if(strlen($mystring) > 5)`.

**Acceptance Scenarios**:

1. **Given** `mystring = 'this is a string'`, **When** o desenvolvedor escreve `if(mystring.length() > 5)`, **Then** o PHP gerado é `if(strlen($mystring) > 5)`.
2. **Given** um método de array `add` recebendo uma chain como argumento, **When** `myArray.add(mystring.toUpperCase())`, **Then** o argumento é emitido como `mb_strtoupper($mystring, 'UTF-8')`.
3. **Given** `if(mystring.contains?('test'))`, **When** compilado, **Then** o PHP gerado é `if(str_contains($mystring, 'test'))`.

---

### User Story 4 — Chain sobre literal (Priority: P2)

O desenvolvedor chama métodos diretamente sobre um valor literal string, int, bool, etc. sem precisar declarar uma variável.

**Why this priority**: Permite expressões concisas em contextos de expressão e atribuição. Frequente em código gerado por IA.

**Independent Test**: Compilar `result = 'my string'.length()` e verificar PHP `$result = strlen('my string')`.

**Acceptance Scenarios**:

1. **Given** o código `result = 'my string'.length()`, **When** compilado, **Then** `$result = strlen('my string')`.
2. **Given** `if('my string'.contains?('my'))`, **When** compilado, **Then** `if(str_contains('my string', 'my'))`.
3. **Given** `'my string'.length()` como statement isolado (sem atribuição, sem contexto pai), **When** compilado, **Then** o Checker lança `CheckerException` com mensagem de dead chain.
4. **Given** `'my string'.show!()` como statement isolado, **When** compilado, **Then** é válido — método void, efeito colateral é o objetivo.

---

### User Story 5 — Chain multi-linha (Priority: P2)

O desenvolvedor quebra uma chain longa em múltiplas linhas para legibilidade. O `.` no início da linha de continuação indica que a quebra de linha não é fim de statement.

**Why this priority**: Chains longas em uma linha são ilegíveis. Multi-linha é essencial para usabilidade.

**Independent Test**: Compilar um `.phs` com chain em 3 linhas e verificar que o PHP gerado é idêntico ao da chain em linha única.

**Acceptance Scenarios**:

1. **Given** uma chain multi-linha com `.` iniciando cada linha de continuação, **When** compilado, **Then** o PHP gerado é idêntico ao da chain em linha única equivalente.
2. **Given** uma EOL antes de `.` na continuação, **When** o parser encontra o `.`, **Then** a EOL anterior é tratada como whitespace e a chain continua sem encerrar o statement.
3. **Given** uma linha que começa com `.` mas sem chain anterior no contexto, **When** compilado, **Then** o Checker lança erro de sintaxe.

---

### User Story 6 — Safe navigation operator `?.` (Priority: P3)

O desenvolvedor usa `?.` após um método que pode retornar `Null` para propagar o null silenciosamente ao invés de receber um erro de compilação.

**Why this priority**: Permite chains sobre métodos nullable sem forçar atribuição + verificação intermediária em todos os casos.

**Independent Test**: Compilar `result = mystring.between('a','b')?.length()` e verificar PHP com guard de null.

**Acceptance Scenarios**:

1. **Given** `result = mystring.between('a','b')?.length()`, **When** compilado, **Then** o PHP gerado captura o resultado de `between` em variável temporária e usa operador ternário: `$__t !== null ? strlen($__t) : null`.
2. **Given** `mystring.between('a','b').length()` sem `?.`, **When** compilado, **Then** o Checker lança `CheckerException`: "Method `between` may return `Null`. Use `?.` to propagate or assign and check before chaining."
3. **Given** uma chain `a?.b?.c()` com dois pontos nullable, **When** compilado, **Then** cada elo propaga null corretamente (se `a` for null, `b` e `c` não são chamados).
4. **Given** `?.` após um método que nunca retorna `Null`, **When** compilado, **Then** o Checker emite warning: "`?.` desnecessário — método nunca retorna `Null`."

---

### Edge Cases

- O que acontece quando uma chain tem apenas um método? (`mystring.length()`) → Válido, emitido como chamada simples sem aninhamento.
- O que acontece quando o tipo retornado por um método não está registrado em `getNewVirtualVariable`? → `CheckerException` com mensagem clara indicando o tipo não suportado.
- O que acontece com `?.` no último elo de uma chain atribuída? (`result = mystring.between('a','b')?.length()`) → `result` tem tipo `Int|Null`.
- O que acontece com chain sobre variável de tipo `Mixed`? → `CheckerException`: "Cannot chain directly after `Mixed` return. Assign to a variable and verify type before chaining."
- O que acontece com chain após método `!` (void)? → `CheckerException`: "Cannot chain after void method."
- O que acontece se o método não existe para o tipo em foco? → `CheckerException` com o nome do método e tipo atual.
- Chain sobre literal numérico: `42.is?('Int')` → Válido, `GeneralType.is?` está disponível para todos os tipos.

---

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: O compilador DEVE permitir encadear chamadas de método sobre variáveis usando a sintaxe `variable.method1(args).method2(args)`.
- **FR-002**: O compilador DEVE permitir encadear chamadas de método sobre literais usando a sintaxe `'literal'.method(args)`.
- **FR-003**: O compilador DEVE emitir PHP inline nested para chains com atribuição (`$result = outer(inner($var))`), sem variáveis temporárias.
- **FR-004**: O compilador DEVE preservar a variável de origem inalterada quando a chain tem atribuição para outra variável.
- **FR-005**: O compilador DEVE suportar auto-atribuição explícita: `myvar = myvar.method()` sobrescreve a variável e atualiza seu tipo no SymbolTable.
- **FR-006**: O compilador DEVE suportar chains em contextos de expressão (condição de `if`, argumento de método) sem atribuição.
- **FR-007**: O compilador DEVE suportar chain multi-linha onde `.` no início da linha de continuação indica continuação da chain.
- **FR-008**: O compilador DEVE suportar o operador `?.` após um método nullable para propagar `null` ao invés de encerrar a chain com erro.
- **FR-009**: O Checker DEVE lançar `CheckerException` quando um método é chamado sobre um tipo que não o define.
- **FR-010**: O Checker DEVE lançar `CheckerException` quando uma chain é encadeada após um método void (`!`).
- **FR-011**: O Checker DEVE lançar `CheckerException` quando uma chain é encadeada após um método nullable sem uso de `?.`.
- **FR-012**: O Checker DEVE lançar `CheckerException` quando uma chain sobre literal (não-void) não tem destino (dead chain).
- **FR-013**: O Checker DEVE lançar `CheckerException` quando uma chain é encadeada diretamente após um método que retorna `Mixed`.
- **FR-014**: O compilador DEVE estender `getNewVirtualVariable` para suportar todos os tipos presentes em `returnOfPhpExecution` dos TypeMethods existentes (incluindo SuperTypes).
- **FR-015**: Os casos de teste em `samples/feature/` que testavam method chaining quebrado DEVEM ser substituídos por novos casos em `samples/success/` e `samples/error/` cobrindo os cenários desta spec.
- **FR-016**: Um arquivo `prompts/method-chaining-out-of-scope.md` DEVE ser criado listando todos os itens fora do escopo desta implementação para referência futura.

### Key Entities

- **Chain**: Sequência de chamadas de método conectadas por `.` ou `?.` sobre uma expressão inicial (variável ou literal).
- **Foco (variableOnFocus)**: O nó AST que representa o valor atual sendo transformado na chain. O `VariableManager` mantém esse estado durante o parse.
- **Elo da chain**: Cada chamada de método individual dentro de uma chain. Cada elo tem um tipo de entrada (o tipo em foco) e um tipo de saída (`returnOfPhpExecution`).
- **Dead chain**: Chain cujo resultado final não tem destino — não é atribuído a variável, não alimenta contexto pai, e não é método void.
- **Safe navigation (`?.`)**: Operador que interrompe a chain e retorna `null` se o elo anterior retornou `null`, sem lançar erro.
- **TypeMethod**: Classe PHP (`StringMethods`, `ArrayMethods`, etc.) que define os métodos disponíveis para um tipo PHireScript e seus templates de emissão PHP.

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Todos os 7 cenários documentados em `prompts/method-chaining-analysis.md` estão cobertos por casos de sucesso ou erro nos samples, e os casos passam no orchestrator (`php bin/stretch`).
- **SC-002**: Um desenvolvedor consegue encadear até 5 métodos em sequência sobre uma variável String sem erro de compilação, e o PHP gerado é validado como sintaxe PHP válida.
- **SC-003**: O Checker detecta e reporta 100% dos casos de dead chain, chain após void, chain após nullable sem `?.`, e chain após `Mixed` — sem falsos negativos nos casos de erro dos samples.
- **SC-004**: A variável de origem é preservada (não mutada) em 100% das chains com atribuição para variável diferente, verificado por asserção nos `CaseValidation` dos samples.
- **SC-005**: Chains sobre literais compilam para PHP equivalente a chains sobre variáveis com o mesmo valor, verificado por comparação de output.
- **SC-006**: O operador `?.` emite PHP com guard de null que retorna `null` ao invés de lançar exceção quando o elo anterior é `null`, verificado por teste de execução PHP nos samples.

---

## Assumptions

- O `if` e outros contextos de expressão já têm infraestrutura suficiente para receber o resultado de uma chain como expressão — o escopo desta feature é o mecanismo de chain, não reimplementar os contextos consumidores.
- `addEnd!`, `addStart!` e outros métodos que internamente usam referência PHP são tratados como fora do escopo desta implementação — seus templates podem estar incorretos e serão revisados em iteração futura após o design de imutabilidade de arrays ser finalizado.
- O suporte a chains sobre tipos de coleção genérica (`List<T>`, `Map<T>`, `Queue<T>`, `Stack<T>`) com sintaxe `Type<SubType>` está fora do escopo — o parser ainda não suporta essa sintaxe fora de declarações de método.
- O mecanismo de `retorno duplo com alerta em runtime` via `Messenger` (para `returnOfPhpExecution: ['String', 'Int']`) está fora do escopo desta implementação — o design está definido mas a implementação é complexa e será feita em iteração futura.
- Os ~5000 métodos nativos PHP não mapeados nos TypeMethods estão fora do escopo — o mapeamento completo será feito em consulta a `discovery/php_api_analysis.html` e `discovery/php_api.html` em momento futuro.
- Resources PHP (streams, file handles, curl, etc.) não têm TypeMethod e estão fora do escopo.
- Bibliotecas PHP customizadas (extensões via php.ini) estão fora do escopo — possível suporte futuro via extensão de `BaseMethods` pelo desenvolvedor.
- Pattern matching sobre resultado de chain está fora do escopo — não implementado no compilador.
- Foreach/Loop sobre chains está fora do escopo — contexto `Loop` ainda é sketch.
- Os casos em `samples/feature/` são considerados descartáveis — serão substituídos por casos bem estruturados em `samples/success/` e `samples/error/`.
