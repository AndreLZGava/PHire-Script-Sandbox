# Tasks: Expansão de Arrow Functions

**Input**: Design documents from `specs/001-expand-arrow-functions/`

**Branch**: `001-expand-arrow-functions`

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Pode rodar em paralelo (arquivos diferentes, sem dependências incompletas)
- **[Story]**: A qual user story a tarefa pertence (US1–US7)

---

## Phase 1: Setup

**Purpose**: Verificar que o ambiente está pronto antes de qualquer mudança

- [X] T001 Confirmar que `composer validate-all` passa com 0 falhas em `phirescript/` (baseline antes das mudanças)
- [X] T002 Confirmar que `php bin/stretch --mode=success` passa todos os 34 casos existentes no sandbox (baseline)

---

## Phase 2: Foundational — Correção do parser (bloqueante para US3+)

**Purpose**: Corrigir o bug de múltiplos parâmetros no parser. Sem isso, US3, US4, US5, US6 não funcionam.

**⚠️ CRÍTICO**: US3 e acima dependem desta fase.

- [X] T003 Criar `phirescript/src/Compiler/Parser/Ast/Resolver/Expressions/ArrowFunctionOpeningParensResolver.php` — resolver que dispara em `isOpeningParenthesis()` sem verificar token anterior; ao resolver, cria `ParamsListNode` e entra em `ParameterListContext` (mesmo comportamento de `OpeningParamsDeclarationResolver` condição 1, mas sem a condição `$before->isIdentifier()`)
- [X] T004 Modificar `phirescript/src/Compiler/Parser/Ast/Context/Declarations/ArrowFunctionDeclarationContext.php` — substituir `new OpeningParamsDeclarationResolver()` no slot `'parameters'` por `new ArrowFunctionOpeningParensResolver()`
- [X] T005 Verificar que PHPStan nível 9 passa em `phirescript/` após T003–T004

**Checkpoint**: Parser aceita arrow functions com 0, 1, e N parâmetros corretamente.

---

## Phase 3: User Story 1+2 — Zero e um parâmetro (Priority: P1) 🎯 MVP

**Goal**: Arrow functions com 0 parâmetros e com 1 parâmetro tipado compilam para PHP correto.

**Independent Test**: `php bin/stretch --mode=success` passa o novo `case_35` com arrow functions sem parâmetros e com 1 parâmetro.

### Implementação

- [X] T006 [US1] Modificar `phirescript/src/Compiler/Emitter/Declarations/ArrowFunctionEmitter.php` — emitir `function()` quando `$node->parameters` for null ou `$node->parameters->params` estiver vazio (atualmente assume params sempre presentes)
- [X] T007 [P] [US2] Verificar emissão de 1 parâmetro tipado: `(Int n): Int` → `function(int $n): int` — sem mudança de código se já funcionar após T004; caso contrário ajustar `ParamArgumentEmitter` em `phirescript/src/Compiler/Emitter/Signatures/ParamArgumentEmitter.php`

### Caso de integração no sandbox

- [X] T008 [US1] Criar `samples/success/case_35/ArrowFunctions.phs` com: arrow function sem parâmetros (`: Void =>`) e arrow function com 1 parâmetro tipado
- [X] T009 [US1] Criar `samples/success/case_35/CaseValidation.php` com `assertHasMessage` verificando compilação bem-sucedida; `pkg PHireScript.Samples35`

**Checkpoint**: US1 e US2 verificados — `php bin/stretch --mode=success` passa case_35.

---

## Phase 4: User Story 3 — Múltiplos parâmetros + union types (Priority: P1)

**Goal**: Arrow functions com 2+ parâmetros tipados compilam corretamente; union types em parâmetros e no tipo de retorno são suportados.

**Independent Test**: `php bin/stretch --mode=success` passa o novo `case_36` com arrow function de 2+ parâmetros e union types.

### Implementação

- [X] T010 [US3] Verificar que após T003–T004 a arrow function `(Float preco, Float taxa): Float => { ... }` compila com `ParamArgumentNode.types` e `name` corretos para cada parâmetro — sem mudança de código adicional se a fase fundacional resolveu; documentar resultado
- [X] T011 [P] [US3] Verificar union types em parâmetros: `(String|Int valor): String|Int` → `string|int $valor`: `string|int` — `ParamArgumentEmitter` já usa `implode('|', $node->types)`; verificar se `ReturnTypeEmitter` também está correto (já implementado); ajustar se necessário

### Caso de integração no sandbox

- [X] T012 [US3] Criar `samples/success/case_36/ArrowFunctionsMultiParam.phs` com: arrow function com 2 parâmetros, arrow function com 3 parâmetros, arrow function com union type em parâmetro e no retorno
- [X] T013 [US3] Criar `samples/success/case_36/CaseValidation.php`; `pkg PHireScript.Samples36`

**Checkpoint**: US3 verificado — `php bin/stretch --mode=success` passa case_36.

---

## Phase 5: User Story 4 — Valores default em parâmetros (Priority: P2)

**Goal**: Parâmetros com valor default (`String nome = "mundo"`, `Int taxa = 0`, `null`) compilam para PHP com default correto.

**Independent Test**: `php bin/stretch --mode=success` passa o novo `case_37` com parâmetros com e sem default.

### Implementação

- [X] T014 [US4] Verificar que `ArgumentAssignmentResolver` + `ArgumentAssignmentContext` já armazenam o valor em `ParamArgumentNode.value` para literais primitivos; testar compilação de `(String nome = "mundo"): String => { return nome }` e confirmar PHP gerado: `function(string $nome = "mundo"): string`
- [X] T015 [P] [US4] Verificar que `ParamArgumentEmitter.emit()` emite o valor default corretamente para SuperTypes e MetaTypes via `$ctx->emitter->emit($node->value, $ctx)` — ajustar se o dispatcher não encontrar o emitter correto
- [X] T016 [P] [US4] Verificar que `ArgumentAssignmentContext` trata corretamente instâncias de tipos importados via `use`/`external` como valor default; ajustar se necessário

### Caso de integração no sandbox

- [X] T017 [US4] Criar `samples/success/case_37/ArrowFunctionDefaults.phs` com: parâmetro com default string, default int, default null, default bool
- [X] T018 [US4] Criar `samples/success/case_37/CaseValidation.php`; `pkg PHireScript.Samples37`

**Checkpoint**: US4 verificado — `php bin/stretch --mode=success` passa case_37.

---

## Phase 6: User Story 5 — Captura automática de variáveis externas (Priority: P2)

**Goal**: Arrow functions que referenciam variáveis do escopo externo compilam com `use ($var)` gerado automaticamente; sem `use` quando não há referências externas.

**Independent Test**: `php bin/stretch --mode=success` passa o novo `case_38` onde PHP gerado inclui `use ($desconto)` sem nenhuma instrução extra no `.phs`.

### Implementação

- [X] T019 [US5] Modificar `phirescript/src/Compiler/Emitter/Declarations/ArrowFunctionEmitter.php` — implementar método privado `collectExternalRefs(MethodScopeNode $body, array $paramNames): array` que percorre recursivamente os filhos de `$body` coletando nomes de `VariableReferenceNode` (ou equivalente) e subtrai os `$paramNames`
- [X] T020 [US5] Modificar `ArrowFunctionEmitter.emit()` — usar `collectExternalRefs()` para gerar a cláusula `use ($var1, $var2)` entre a lista de parâmetros e o tipo de retorno quando o array não estiver vazio; omitir `use` quando vazio
- [X] T021 [P] [US5] Verificar o caso especial de `$this` dentro de método de classe: se `$this` é referenciado no corpo, deve aparecer em `use ($this)` automaticamente

### Caso de integração no sandbox

- [X] T022 [US5] Criar `samples/success/case_38/ArrowFunctionCapture.phs` com: arrow function que usa variável do escopo (`desconto`), arrow function que usa múltiplas variáveis externas, arrow function que não usa variáveis externas (sem `use`)
- [X] T023 [US5] Criar `samples/success/case_38/CaseValidation.php`; `pkg PHireScript.Samples38`

**Checkpoint**: US5 verificado — `php bin/stretch --mode=success` passa case_38.

---

## Phase 7: User Story 6+7 — Aninhadas e como argumento (Priority: P3)

**Goal**: Arrow functions aninhadas compilam para funções anônimas PHP aninhadas; arrow function passada como argumento inline é emitida corretamente.

**Independent Test**: Compilação de arquivo `.phs` com arrow function aninhada não lança erro e PHP gerado é válido (`php -l`).

### Implementação

- [X] T024 [P] [US6] Verificar se `ArrowFunctionEmitter` já suporta aninhamento recursivamente via `$ctx->emitter->emit($node->bodyCode, $ctx)` — se `MethodScopeEmitter` delega ao dispatcher para cada statement, aninhamento pode funcionar sem mudança; testar e ajustar se necessário
- [X] T025 [P] [US6] Verificar que `collectExternalRefs()` (T019) trata corretamente variáveis capturadas de dois níveis de escopo (parâmetros da arrow externa disponíveis na arrow interna via `use`)
- [X] T026 [P] [US7] Verificar se `ArrowFunctionResolver` já dispara dentro de `FunctionCallContext` ou `ArgumentCallContext` (arrow function como argumento inline) — ajustar contextos de chamada se necessário

### Casos de integração no sandbox

- [X] T027 [US6] Criar `samples/success/case_39/ArrowFunctionNested.phs` com arrow functions aninhadas (2 níveis)
- [X] T028 [US6] Criar `samples/success/case_39/CaseValidation.php`; `pkg PHireScript.Samples39`

**Checkpoint**: US6 e US7 verificados.

---

## Phase 8: Checker Semântico (Cross-cutting)

**Purpose**: Validação no checker aplicável a todos os cenários acima.

- [X] T029 Criar `phirescript/src/Compiler/Checker/Declaration/ArrowFunction/ArrowFunctionChecker.php` com `#[CompilerPass(order: 8)]` — implementar três validações: (a) corpo vazio + tipo de retorno ≠ Void → `CheckerException`; (b) `return valor` + tipo de retorno = Void → `CheckerException`; (c) tipo de retorno ≠ Void + nenhum `return` no corpo → `CheckerException`
- [X] T030 [P] Criar `phirescript/tests/Compiler/ArrowFunctionCheckerTest.php` — cobrir os três casos de erro e o caso feliz (retorno Void com corpo vazio, retorno não-Void com `return` correto)
- [X] T031 [P] Verificar que `PassDiscovery` auto-descobre `ArrowFunctionChecker` via reflexão (confirmar que o namespace segue o padrão dos outros checkers)

---

## Phase 9: Polish & Qualidade

- [X] T032 [P] Executar `composer quality` em `phirescript/` (refactor + format + analyse) — corrigir qualquer issue levantado pelo PHPStan ou php-cs-fixer
- [X] T033 Executar `php bin/stretch --mode=success` — confirmar 0 regressões nos 34 casos existentes + novos casos passando
- [X] T034 [P] Verificar que `samples/feature/case_12/ArrowFunctions.phs` ainda compila sem erro (o caso feature original)
- [ ] T035 Commit único com mensagem `feat(parser): expand arrow function support with typed params, defaults, and auto-use capture`

---

## Dependencies & Execution Order

### Dependências entre fases

- **Phase 1 (Setup)**: Nenhuma — executar imediatamente
- **Phase 2 (Foundational)**: Depende de Phase 1 — **bloqueia US3, US4, US5, US6**
- **Phase 3 (US1+US2)**: Pode começar após Phase 2 (US1 teoricamente independente, mas melhor ter o parser correto)
- **Phase 4 (US3)**: Depende de Phase 2
- **Phase 5 (US4)**: Depende de Phase 3 (parser correto)
- **Phase 6 (US5)**: Depende de Phase 3 (emitter base)
- **Phase 7 (US6+US7)**: Depende de Phase 3+6
- **Phase 8 (Checker)**: Independente — pode rodar em paralelo com Phase 3–7
- **Phase 9 (Polish)**: Depende de todas as fases anteriores

### Dependências entre user stories

- **US1+US2 (P1)**: Bloqueiam na Phase 2 apenas; independentes entre si
- **US3 (P1)**: Depende de Phase 2 (parser fix)
- **US4 (P2)**: Depende de US1+US2 funcionarem
- **US5 (P2)**: Depende de US1+US2 funcionarem
- **US6+US7 (P3)**: Dependem de US1–US5

### Oportunidades de paralelismo

- T003 e T030 (resolver novo e testes do checker) podem ser escritos em paralelo
- T008+T009 (case_35) e T030 (ArrowFunctionCheckerTest) podem ser escritos em paralelo após T006
- T017+T018, T022+T023, T027+T028 (casos de sandbox) podem ser escritos em paralelo entre si após suas respectivas implementações

---

## Parallel Example: Phase 2 + Phase 8

```
# Pode rodar em paralelo:
Task T003: ArrowFunctionOpeningParensResolver.php (parser)
Task T030: ArrowFunctionCheckerTest.php (unit tests do checker)
```

---

## Implementation Strategy

### MVP (US1+US2 — Cenários 1 e 2)

1. ✅ Phase 1: Verificar baseline
2. Phase 2: Corrigir parser (T003–T005)
3. Phase 3: Emitter zero/1 param + case_35 (T006–T009)
4. **PARAR e VALIDAR**: `php bin/stretch --mode=success` com case_35

### Entrega incremental

1. MVP acima → fase base funcional
2. Phase 4 (US3) → múltiplos params + case_36
3. Phase 5 (US4) → defaults + case_37
4. Phase 6 (US5) → captura automática + case_38
5. Phase 7 (US6+US7) → aninhadas + inline
6. Phase 8 (Checker) + Phase 9 (Polish) → qualidade final

---

## Notes

- [P] = arquivos diferentes, sem dependências incompletas
- Cada sandbox case usa `pkg PHireScript.SamplesN` onde N = número da pasta
- PHPStan nível 9 sem supressões é gate obrigatório
- Commit único ao final quando todos os casos passarem
