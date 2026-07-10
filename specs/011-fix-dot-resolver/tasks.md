# Tasks: User-Defined Method Calls as Expression Operands (BB-3 completion)

**Input**: Design documents from `specs/011-fix-dot-resolver/`

**Organization**: Tasks grouped by user story — each phase is independently testable.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no shared dependencies)
- **[Story]**: Which user story this task belongs to
- Include exact file paths in descriptions

---

## Phase 1: Setup

**Purpose**: Create sandbox case directories.

- [x] T001 Create sandbox case directories `samples/success/case_64/` and `samples/success/case_76/`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Modificações no compiler que bloqueiam todas as user stories.

**⚠️ CRITICAL**: Nenhuma user story pode ser validada até esta fase estar completa.

- [x] T002 [P] Adicionar campos `currentClassName`, `currentClassMethods` e `classMethodRegistry` ao `ParseContext` em `phirescript/src/Compiler/Parser/ParseContext.php` — tipos: `?string`, `array`, `array`; inicializar como `null`, `[]`, `[]`; sem lógica — apenas os campos

- [x] T003 Implementar `extractMethodSignatures(TokenManager $tm): array` em `phirescript/src/Compiler/Parser/Ast/Resolver/Root/Class/ClassBodyResolver.php` — método privado que usa `peek($offset)` incrementalmente a partir do offset 0; conta profundidade de `{`/`}` (começa em 1 ao entrar); para cada sequência `T_HASH → T_IDENTIFIER(name) → T_OPEN_PAREN → ... → T_CLOSE_PAREN → T_COLON → T_IDENTIFIER(returnType)` encontrada na profundidade 1, registra `$methods[$name] = $returnType`; retorna ao encontrar `}` que fecha a classe (depth volta a 0) ou ao atingir fim dos tokens — depende de T002

- [x] T00x Modificar `ClassBodyResolver.resolve()` em `phirescript/src/Compiler/Parser/Ast/Resolver/Root/Class/ClassBodyResolver.php` para: (1) extrair `$className = $context->node->name` e `$extendsName = $context->node->extends?->name ?? null`; (2) chamar `$methods = $this->extractMethodSignatures($parseContext->tokenManager)`; (3) setar `$parseContext->currentClassName = $className`, `$parseContext->currentClassMethods = $methods`, `$parseContext->classMethodRegistry[$className] = ['methods' => $methods, 'extends' => $extendsName]`; manter o comportamento existente de criar `ClassBodyNode` e entrar em `ClassBodyContext` — depende de T003

- [x] T00x Limpar `currentClassName` e `currentClassMethods` ao fechar `ClassBodyContext`: implementar `afterClose(Token $token, ParseContext $parseContext): void` em `phirescript/src/Compiler/Parser/Ast/Context/Declarations/Class/ClassBodyContext.php` setando `$parseContext->currentClassName = null` e `$parseContext->currentClassMethods = []`; verificar se `afterClose` já existe — se sim, adicionar os dois setters ao final do método existente — depende de T004

- [x] T00x Implementar método privado `resolveFromClassHierarchy(string $methodName, ParseContext $parseContext): ?string` em `phirescript/src/Compiler/Parser/Ast/Resolver/Expressions/FunctionCallResolver.php` — percorre `classMethodRegistry` partindo de `$parseContext->currentClassName`; loop: se `$entry['methods'][$methodName]` existe retorna o tipo de retorno; senão move para `$entry['extends']`; retorna `null` ao esgotar a cadeia ou se `currentClassName` for null — depende de T002

- [x] T00x Modificar `FunctionCallResolver.isTheCase()` em `phirescript/src/Compiler/Parser/Ast/Resolver/Expressions/FunctionCallResolver.php` para adicionar, **antes** do `return` existente, o seguinte branch: `if ($focus instanceof ThisExpressionNode && $token->isIdentifier() && $parseContext->tokenManager->getNextTokenAfterCurrent()->isOpeningParenthesis() && $this->resolveFromClassHierarchy($token->value, $parseContext) !== null) { return true; }` — adicionar `use PHireScript\Compiler\Parser\Ast\Nodes\Expressions\ThisExpressionNode;` ao bloco de imports — depende de T006

- [x] T00x Modificar `FunctionCallResolver.resolve()` em `phirescript/src/Compiler/Parser/Ast/Resolver/Expressions/FunctionCallResolver.php` para adicionar, **antes** do código de resolução via `SymbolTableManager`, o seguinte branch: `if ($focus instanceof ThisExpressionNode && $parseContext->currentClassName !== null) { $returnType = $this->resolveFromClassHierarchy($token->value, $parseContext); if ($returnType !== null) { $function = new FunctionNode(token: $token); $function->variableBase = $focus; $virtualVar = $this->getNewVirtualVariable($token, $returnType); $parseContext->variables->setVirtualVariable($virtualVar); $parseContext->contextManager->enter(new FunctionCallContext($function)); $context->addChild($function); return; } }` — depende de T007

**Checkpoint**: Compilar um arquivo `.ps` com `result = this.getBase() * 10` deve compilar sem exception. Executar `php phirescript/bin/build` com um caso temporário para verificar.

---

## Phase 3: User Story 1 — Método de usuário como operando em assignment (Priority: P1) 🎯 MVP

**Goal**: `result = this.getBase() * this.getRate()` compila corretamente dentro de um método de classe.

**Independent Test**: Compilar `samples/success/case_64/` com `php phirescript/bin/build` e verificar que o PHP gerado contém `$result = $this->getBase() * $this->getRate()`.

- [x] T00x [P] [US1] Criar `samples/success/case_64/Calculator.ps` com package `pkg PHireScript.Samples64` — classe `Calculator as scoped` com propriedades `Int base` e `Float rate`; métodos `# getBase(): Int { return this.base }`, `# getRate(): Float { return this.rate }`, `# total(): Float { result = this.getBase() * this.getRate() \n return result }`, `# withBonus(): Float { return (this.getBase() + 10) * this.getRate() }`

- [x] T01x [P] [US1] Criar `samples/success/case_64/CalculatorTest.php` com namespace `PHireScript\Sandbox\src\output` — PHPUnit test que instancia `Calculator` e valida: `getBase()` retorna `int`, `getRate()` retorna `float`, `total()` retorna `getBase() * getRate()`, `withBonus()` retorna `(getBase() + 10) * getRate()`

- [x] T01x [US1] Criar `samples/success/case_64/CaseValidation.php` com namespace `Sandbox\Samples\success\case_64` — `execute()` asserta `✔ src/output/Calculator.ps`; `executeTest()` asserta: `str_contains($output, '$result = $this->getBase() * $this->getRate()')`, `str_contains($output, 'return ($this->getBase() + 10) * $this->getRate()')` — depende de T009

- [x] T01x [US1] Gerar snapshot `samples/success/case_64/Calculator.psc` executando `php phirescript/bin/snapshot` com `source` apontando para `samples/success/case_64` no `PHireScript.json` — depende de T009, T008

**Checkpoint**: `php bin/stretch --mode=success --from=64 --to=64` deve passar.

---

## Phase 4: User Story 2 — Dois métodos de usuário em expressão binária (Priority: P1)

**Goal**: `return this.getBase() * this.getRate()` compila corretamente (variação sem assignment intermediário).

**Independent Test**: Case_64 já cobre este cenário via método `total()`. Esta fase valida a suite completa sem regressões.

- [x] T01x [US2] Executar `php bin/stretch --mode=success --from=1 --to=69` e confirmar zero regressões — depende de T012

**Checkpoint**: Todos os cases 1–69 passam.

---

## Phase 5: User Story 3 — Herança transitiva (Priority: P2)

**Goal**: Método herdado via `extends` é reconhecido como válido em `this.method()`.

**Independent Test**: Compilar `samples/success/case_76/` onde `Child extends Base` e `Child.doubled()` chama `this.getValue()` declarado em `Base`. PHP gerado: `return $this->getValue() * 2`.

- [x] T01x [P] [US3] Criar `samples/success/case_76/Base.ps` com `pkg PHireScript.Samples76` — `class Base as scoped` com `Int value` e `# getValue(): Int { return this.value }`

- [x] T01x [P] [US3] Criar `samples/success/case_76/Child.ps` com `pkg PHireScript.Samples76` — `class Child extends Base { # doubled(): Int { return this.getValue() * 2 } }` — usa `use PHireScript.Samples76.{Base}`

- [x] T01x [P] [US3] Criar `samples/success/case_76/Case76Test.php` com namespace `PHireScript\Sandbox\src\output` — PHPUnit test que carrega `Child.php`, instancia com `value = 5`, asserta `doubled()` retorna `10`

- [x] T01x [US3] Criar `samples/success/case_76/CaseValidation.php` com namespace `Sandbox\Samples\success\case_76` — `execute()` asserta `✔ src/output/Base.ps` e `✔ src/output/Child.ps`; `executeTest()` asserta que `Child.php` contém `return $this->getValue() * 2` — depende de T014, T015

- [x] T01x [US3] Gerar snapshots `samples/success/case_76/Base.psc` e `samples/success/case_76/Child.psc` — depende de T014, T015, T008

**Checkpoint**: `php bin/stretch --mode=success --from=76 --to=76` deve passar.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Qualidade e validação final.

- [x] T01x Executar suite completa `php bin/stretch --mode=success` e confirmar zero regressões em todos os cases

- [x] T02x [P] Verificar que `FunctionCallResolver.php` não tem `use` statements duplicados ou não utilizados após as modificações de T007 e T008

- [x] T02x [P] Atualizar `agents/pm/backlog.md`: remover ou marcar como resolvido o item BB-3 (completude); atualizar T019/T020 da spec 006 como feitos

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 (Setup)**: Sem dependências — iniciar imediatamente
- **Phase 2 (Foundational)**: Depende de Phase 1 — **bloqueia todas as user stories**
  - T002 [P] com T006 (arquivos diferentes, independentes)
  - T003 → T004 → T005 (sequencial, mesmo arquivo)
  - T006 → T007 → T008 (sequencial, mesmo arquivo)
- **Phase 3 (US1)**: Depende de Phase 2 — T009 e T010 [P] com T008
- **Phase 4 (US2)**: Depende de Phase 3
- **Phase 5 (US3)**: Depende de Phase 2 — T014, T015, T016 [P] entre si
- **Phase 6 (Polish)**: Depende de Phases 3, 4 e 5

### Parallel Opportunities

```bash
# Phase 2 — rodar em paralelo:
T002  # ParseContext (arquivo independente)
T006  # resolveFromClassHierarchy (arquivo de FunctionCallResolver, mas método privado novo)
# depois T002:
T003 → T004 → T005  # ClassBodyResolver + ClassBodyContext (sequencial)
# depois T006:
T007 → T008  # FunctionCallResolver.isTheCase + resolve (sequencial)

# Phase 3 — rodar em paralelo após T008:
T009  # Calculator.ps
T010  # CalculatorTest.php

# Phase 5 — rodar em paralelo após Phase 2:
T014  # Base.ps
T015  # Child.ps
T016  # Case76Test.php
```

---

## Implementation Strategy

### MVP (US1 — case_64)

1. Completar Phase 1: Setup
2. Completar Phase 2: Foundational — **bloqueia tudo**
3. Completar Phase 3: case_64 (Calculator)
4. **PARAR e VALIDAR**: `php bin/stretch --mode=success --from=64 --to=64`
5. Confirmar sem regressões: `php bin/stretch --mode=success --from=1 --to=69`

### Full Delivery

1. MVP acima
2. Phase 5 (US3 — herança): T014–T018 em paralelo onde possível
3. Phase 6 (Polish): validação final

---

## Notes

- [P] tasks = arquivos diferentes, sem dependências entre si
- [Story] label mapeia a task para a user story correspondente
- Regra crítica: nunca chamar `$tokenManager->advance()` em Resolvers ou Contexts
- `extractMethodSignatures` usa APENAS `peek($offset)` — read-only
- `getNewVirtualVariable()` já existe em `FunctionCallResolver` — reutilizar sem duplicar
- Commit após cada fase validada
