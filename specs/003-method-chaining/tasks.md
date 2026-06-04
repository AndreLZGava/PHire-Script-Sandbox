# Tasks: Method Chaining

**Input**: Design documents from `specs/003-method-chaining/`

**Prerequisites**: plan.md ✓, spec.md ✓, research.md ✓, data-model.md ✓, contracts/ ✓

**Organization**: Tasks agrupadas por user story seguindo a ordem do pipeline do compilador (Scanner → Parser → Checker → Emitter → Sandbox).

---

## Phase 1: Setup

**Purpose**: Criar arquivo de out-of-scope e preparar o ambiente antes de qualquer mudança no compilador.

- [x] T001 Criar `prompts/method-chaining-out-of-scope.md` listando todos os itens fora do escopo desta implementação (resources, typed collections, ~5000 funções PHP, addEnd! por referência, foreach/loop, pattern matching, retorno duplo com Messenger)
- [x] T002 Atualizar `PHireScript.json` source para `samples/success/case_42` para validação incremental durante o desenvolvimento

---

## Phase 2: Foundational (Bloqueadores raiz — fixes cirúrgicos no Parser)

**Purpose**: Corrigir os 3 bloqueadores raiz que impedem qualquer chain de funcionar. Todos os user stories dependem desta fase.

**⚠️ CRÍTICO**: Nenhum user story pode ser testado sem esta fase completa.

- [x] T003 Remover a condição `!== '.'` de `VariableReferenceResolver.isTheCase()` em `phirescript/src/Compiler/Parser/Ast/Resolver/Expressions/Types/VariableReferenceResolver.php:18` — permitir que variáveis seguidas de `.` sejam reconhecidas e setadas como `variableOnFocus`
- [x] T004 Implementar `DotResolver.resolve()` em `phirescript/src/Compiler/Parser/Ast/Resolver/Statements/DotResolver.php` — recuperar `end($context->children)` e chamar `$parseContext->variables->setVirtualVariable($last)` para transferir o foco ao último nó filho
- [x] T005 Descomentar e ativar `$parseContext->variables->setVirtualVariable($function)` em `phirescript/src/Compiler/Parser/Ast/Resolver/Expressions/FunctionCallResolver.php:87` — tornar o FunctionNode recém-criado o novo foco para o próximo elo da chain
- [x] T006 Estender `getNewVirtualVariable()` em `phirescript/src/Compiler/Parser/Ast/Resolver/Expressions/FunctionCallResolver.php` — adicionar suporte para `Null` (→ `NullNode`), `Void` (→ sinaliza fim de chain, sem node), `Mixed` (→ `LiteralNode` genérico), e SuperTypes como `Email`, `Uuid`, `Url`, `Color`, `Cron`, `Duration`, `Json`, `Mac`, `Slug`, `Ipv4`, `Ipv6` (→ `LiteralNode` com o nome do tipo)

**Checkpoint**: Após T003–T006, `variables.add('key','val').add(['k':0],'v').destroy!('key')` do case_13 ainda deve passar. Rodar `php bin/stretch --mode=success` para regressão.

---

## Phase 3: User Story 1 — Chain básica com atribuição (P1) 🎯 MVP

**Goal**: `processed = mystring.replace('a','b').length()` compila para `$processed = strlen(str_replace('a', 'b', $mystring))` com `$mystring` inalterado.

**Independent Test**: `php bin/stretch --mode=success --tags=method-chaining` com case_42 passando.

- [x] T007 [US1] Criar `samples/success/case_42/` com arquivo `StringChain.ps` exercitando chain de 2 e 3 métodos String com atribuição, incluindo `pkg PHireScript.Samples42` e `use` statements necessários
- [x] T008 [US1] Criar `samples/success/case_42/CaseValidation.php` com assertions que verificam: (a) PHP gerado contém inline nested (`strlen(str_replace(...))`), (b) variável de origem não é reatribuída no PHP gerado, (c) tipo da variável de destino é correto
- [x] T009 [US1] Validar que o `FunctionEmitter` em `phirescript/src/Compiler/Emitter/Declarations/FunctionEmitter.php` emite corretamente `@self` recursivo para chains — se não emitir inline nested, ajustar a lógica de substituição de `@self` para usar `emit(node->variableBase)` ao invés de `$variableBase->name`

**Checkpoint**: `php bin/stretch --mode=success --tags=method-chaining` com case_42 verde. Regressão completa com `php bin/stretch --mode=success`.

---

## Phase 4: User Story 2 — Auto-atribuição explícita (P1)

**Goal**: `mystring = mystring.toUpperCase().replace('A','B')` compila para `$mystring = str_replace('A', 'B', mb_strtoupper($mystring, 'UTF-8'))` e o tipo de `mystring` é atualizado no SymbolTable.

**Independent Test**: case_43 passando no orchestrator.

- [x] T010 [US2] Criar `samples/success/case_43/` com arquivo `AutoAssignment.ps` exercitando auto-atribuição com chain — mesma variável no lado esquerdo e direito do `=`, incluindo caso onde o tipo muda (String → Int via `.length()`)
- [x] T011 [US2] Criar `samples/success/case_43/CaseValidation.php` com assertions que verificam que a variável é reatribuída com o valor inline e que o PHP gerado é válido
- [x] T012 [US2] Verificar em `phirescript/src/Compiler/Parser/Ast/Context/Expressions/AssignmentContext.php` que `$this->node->left->type = $this->children[0]` atualiza o tipo corretamente quando o lado direito é uma chain — ajustar se necessário para propagar o tipo de retorno do último elo ao SymbolTable

**Checkpoint**: case_43 verde. Regressão completa.

---

## Phase 5: User Story 3 — Chain em contexto de expressão (P2)

**Goal**: `if(mystring.length() > 5)` compila para `if(strlen($mystring) > 5)` sem atribuição.

**Independent Test**: case_44 passando no orchestrator.

- [x] T013 [US3] Criar `samples/success/case_44/` com arquivo `ChainInExpression.ps` exercitando: chain na condição de `if`, chain como argumento de método (`myArray.add(mystring.toUpperCase())`), e chain com `contains?` retornando Bool diretamente para o `if`
- [x] T014 [US3] Criar `samples/success/case_44/CaseValidation.php` com assertions verificando o PHP gerado para cada cenário
- [x] T015 [US3] Verificar se o `DotResolver` em `AssignmentContext` também funciona corretamente para contextos de expressão (IfConditionContext, FunctionCallContext como argumento) — se o foco não for transferido corretamente nesses contextos, adicionar `DotResolver` nas listas de resolvers dos contextos afetados

**Checkpoint**: case_44 verde. Regressão completa.

---

## Phase 6: User Story 4 — Chain sobre literal (P2)

**Goal**: `result = 'my string'.length()` compila para `$result = strlen('my string')`.

**Independent Test**: case_45 passando no orchestrator.

- [x] T016 [US4] Criar `samples/success/case_45/` com arquivo `LiteralChain.ps` exercitando: chain sobre string literal com atribuição, chain sobre literal em `if`, chain sobre literal como argumento, e `'my string'.show!()` standalone (void — válido)
- [x] T017 [US4] Criar `samples/success/case_45/CaseValidation.php` com assertions
- [x] T018 [US4] Verificar se `StringLiteralResolver` (ou equivalente) seta o `variableOnFocus` para o `StringNode` quando o próximo token é `.` — se não, adicionar lógica equivalente ao fix T003 para literais. Arquivo: `phirescript/src/Compiler/Parser/Ast/Resolver/Expressions/Types/StringLiteralResolver.php`

**Checkpoint**: case_45 verde. Regressão completa.

---

## Phase 7: User Story 5 — Chain multi-linha (P2)

**Goal**: Chain com `.` no início da linha de continuação compila identicamente à chain em linha única.

**Independent Test**: case_47 passando no orchestrator com output PHP idêntico ao case_42.

- [x] T019 [US5] Investigar o comportamento atual do `EndOfLineResolver` dentro de `FunctionCallContext` — verificar se `afterClose()` em `phirescript/src/Compiler/Parser/Ast/Context/Expressions/FunctionCallContext.php` encerra o contexto pai quando encontra EOL seguido de `.`
- [x] T020 [US5] Se necessário, adicionar lógica de lookahead no `EndOfLineResolver` dentro de `FunctionCallContext` para não encerrar o contexto se o próximo token não-whitespace for `.` — arquivo: verificar `phirescript/src/Compiler/Parser/Ast/Resolver/Statements/EndOfLineResolver.php` e o `afterClose()` do FunctionCallContext
- [x] T021 [US5] Criar `samples/success/case_47/` com arquivo `MultiLineChain.ps` exercitando chain em 3+ linhas com `.` no início de cada linha de continuação
- [x] T022 [US5] Criar `samples/success/case_47/CaseValidation.php` com assertions que comparam o output com o equivalente inline

**Checkpoint**: case_47 verde. Regressão completa.

---

## Phase 8: User Story 6 — Safe navigation operator `?.` (P3)

**Goal**: `result = mystring.between('a','b')?.length()` compila para PHP com guard de null.

**Independent Test**: case_46 passando no orchestrator com PHP que retorna `null` ao invés de explodir quando `between` retorna `null`.

- [x] T023 [US6] Adicionar token `?.` no `phirescript/src/Compiler/Scanner.php` com padrão regex `\?\.` — deve ter prioridade superior ao token `.` isolado para evitar ambiguidade
- [x] T024 [US6] Adicionar método `isSafeNavigation(): bool` em `phirescript/src/Compiler/Parser/Managers/Token/Token.php` retornando `$this->value === '?.'`
- [x] T025 [US6] Criar `SafeNavigationResolver` em `phirescript/src/Compiler/Parser/Ast/Resolver/Statements/SafeNavigationResolver.php` — `isTheCase()` retorna true quando `$token->isSafeNavigation()`; `resolve()` transfere o foco (igual ao DotResolver fix T004) e marca o próximo FunctionNode com `$safeNavigation = true`
- [x] T026 [US6] Adicionar campo `public bool $safeNavigation = false` em `phirescript/src/Compiler/Parser/Ast/Nodes/Declarations/FunctionNode.php`
- [x] T027 [US6] Registrar `SafeNavigationResolver` nos contextos que têm `DotResolver`: `AssignmentContext`, `ProgramContext`, `FunctionCallContext`
- [x] T028 [US6] Atualizar `FunctionCallContext.canClose()` em `phirescript/src/Compiler/Parser/Ast/Context/Expressions/FunctionCallContext.php` para incluir `|| $token->isSafeNavigation()` na condição de fechamento
- [x] T029 [US6] Atualizar `FunctionEmitter` em `phirescript/src/Compiler/Emitter/Declarations/FunctionEmitter.php` — quando `$node->safeNavigation === true`, emitir `$__chain_N = {expressão do elo anterior}; {resultado} = $__chain_N !== null ? {expressão do elo atual substituindo @self por $__chain_N} : null`
- [x] T030 [US6] Criar `samples/success/case_46/` com arquivo `SafeNavigation.ps` exercitando `?.` após método nullable (`between`) e chain de dois `?.` consecutivos
- [x] T031 [US6] Criar `samples/success/case_46/CaseValidation.php` com assertions verificando o PHP gerado com guard de null e executar o PHP para confirmar que retorna `null` ao invés de exceção

**Checkpoint**: case_46 verde. Regressão completa.

---

## Phase 9: Checker — 5 Regras de Validação Semântica

**Purpose**: Implementar o `ChainConsistencyChecker` com as 5 regras definidas em `contracts/chain-checker-rules.md`. Depende das phases 2–8 para ter FunctionNodes bem formados no AST.

- [x] T032 [P] Criar `phirescript/src/Compiler/Checker/Expression/ChainConsistencyChecker.php` implementando `Checker` base com `#[CompilerPass(order: 50)]` — `mustCheck()` retorna true para `FunctionNode` com `$isChainLink = true`
- [x] T033 Implementar Regra 1 (continuidade de tipo) em `ChainConsistencyChecker`: verificar que o método chamado existe no TypeMethods do tipo de entrada do elo — lançar `CheckerException` se não existir
- [x] T034 Implementar Regra 2 (void termina chain) em `ChainConsistencyChecker`: verificar que `variableBase` não é um FunctionNode com `returnOfPhpExecution = []` — lançar `CheckerException` se for
- [x] T035 Implementar Regra 3 (nullable requer `?.`) em `ChainConsistencyChecker`: verificar que quando `variableBase->method->returnOfPhpExecution` contém `Null`, o FunctionNode atual tem `$safeNavigation = true` — lançar `CheckerException` se não
- [x] T036 Implementar Regra 4 (dead chain) em `ChainConsistencyChecker`: verificar que FunctionNode não-void cujo pai não é `AssignmentNode`, `IfConditionNode` ou argumento de outro FunctionNode → lançar `CheckerException`
- [x] T037 Implementar Regra 5 (Mixed bloqueia chain) em `ChainConsistencyChecker`: verificar que `variableBase->method->returnOfPhpExecution = ['Mixed']` não é seguido de chain direta → lançar `CheckerException`
- [x] T038 Implementar warning de `?.` desnecessário: quando `$safeNavigation = true` mas o elo anterior não tem `Null` em `returnOfPhpExecution` → `Messenger::warning()` (não erro)
- [x] T039 Registrar `ChainConsistencyChecker` em `phirescript/src/Compiler/Checker.php`
- [x] T040 Criar `samples/error/case_49/` com 4 arquivos `.ps` separados exercitando cada violação: dead chain, chain após void, nullable sem `?.`, e Mixed direto
- [x] T041 Criar `samples/error/case_49/CaseValidation.php` com assertions que verificam que cada arquivo `.ps` produz a `CheckerException` correta com a mensagem esperada
- [x] T042 Adicionar campo `public bool $isChainLink = false` em `phirescript/src/Compiler/Parser/Ast/Nodes/Declarations/FunctionNode.php` e setar para `true` no `FunctionCallResolver.resolve()` quando `variableOnFocus` é um FunctionNode (ou seja, o elo anterior já é uma chain)

---

## Phase 10: Chain cruzando tipos + casos adicionais de sucesso

**Purpose**: Casos de sucesso restantes (case_43 revisitado se necessário, case_48).

- [x] T043 Criar `samples/success/case_48/` com arquivo `CrossTypeChain.ps` exercitando chain que cruza tipos: `String → Array` via `split()`, depois `Array → Int` via `length()`, com atribuição em cada passo
- [x] T044 Criar `samples/success/case_48/CaseValidation.php` com assertions verificando os tipos em cada passo e o PHP gerado

---

## Phase 11: Polish & Documentação

**Purpose**: Sincronizar a documentação com a implementação concluída.

- [x] T045 [P] Atualizar `phirescript/CLAUDE.md` — mover Method Chaining de "Sketch/Partial" para "Functional" na seção Language Feature Status, listar os sandbox cases 42–49
- [x] T046 [P] Atualizar `phirescript/architecture.md` — adicionar `SafeNavigationResolver` na Source Tree, adicionar `ChainConsistencyChecker` na tabela de Checkers, adicionar token `?.` nos tipos de token
- [x] T047 [P] Atualizar `knowledge_base/AGENTS.md` — atualizar contagem de cases (adicionar 42–49 = 8 novos cases)
- [x] T048 [P] Atualizar `knowledge_base/brief.md` — atualizar contagem de cases
- [x] T049 [P] Atualizar `knowledge_base/skills/write-phirescript/SKILL.md` — documentar sintaxe de method chaining disponível para uso em `.ps`
- [x] T050 Rodar `composer quality` dentro de `phirescript/` e corrigir qualquer violação de PHPStan nível 9, Rector, ou php-cs-fixer introduzida pelos novos arquivos
- [x] T051 Rodar `vendor/bin/phpunit` dentro de `phirescript/` e verificar que não há regressões nos testes unitários existentes
- [x] T052 Rodar `php bin/stretch --mode=success` completo e `php bin/stretch --mode=error` para validação final de regressão

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 (Setup)**: Sem dependências — pode começar imediatamente
- **Phase 2 (Foundational)**: Depende de Phase 1 — **BLOQUEIA todos os user stories**
- **Phases 3–8 (User Stories)**: Dependem de Phase 2. Podem ser executadas sequencialmente (recomendado para validação incremental)
- **Phase 9 (Checker)**: Depende de Phases 3–8 (FunctionNodes precisam estar bem formados)
- **Phase 10**: Depende de Phase 2 (bloqueador raiz resolvido)
- **Phase 11 (Polish)**: Depende de todas as phases anteriores

### User Story Dependencies

- **US1 (P1)**: Depende apenas de Phase 2 — MVP mínimo
- **US2 (P1)**: Depende de US1 (mecanismo de foco) — pode ser executado logo após
- **US3 (P2)**: Depende de Phase 2 — independente de US1/US2 tecnicamente
- **US4 (P2)**: Depende de Phase 2 — literais seguem o mesmo mecanismo
- **US5 (P2)**: Depende de US1 (multi-line é extensão de chain básica)
- **US6 (P3)**: Depende de US1 — safe navigation requer chain básica funcionando

### Parallel Opportunities

- T003, T004, T005 podem ser implementados em paralelo (arquivos diferentes)
- T007, T008 (case_42 .ps e CaseValidation.php) podem ser criados em paralelo
- T045, T046, T047, T048, T049 (documentação Phase 11) são todos independentes

---

## Implementation Strategy

### MVP (US1 apenas — Phases 1–3)

1. T001–T002: Setup
2. T003–T006: Fixes do bloqueador raiz
3. T007–T009: case_42 + ajuste FunctionEmitter
4. **VALIDAR**: `php bin/stretch --mode=success --tags=method-chaining` verde
5. **Regressão**: `php bin/stretch --mode=success` completo

### Incremental

- US1 → validar → US2 → validar → US3 → validar → US4 → US5 → US6 → Checker → Polish
- Rodar `php bin/stretch` após cada case adicionado

---

## Notes

- `[P]` = arquivos diferentes, sem dependência entre si — podem ser executados em paralelo
- `[USN]` = user story correspondente da spec.md
- Cada checkpoint inclui regressão completa para garantir que os fixes do bloqueador raiz não quebram os 41 cases existentes
- O `composer quality` dentro de `phirescript/` deve ser executado antes de qualquer commit no compilador
- Casos em `samples/feature/` não são tocados — são descartados conforme spec.md
