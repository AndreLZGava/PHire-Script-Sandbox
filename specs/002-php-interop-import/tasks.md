# Tasks: PHP Interop — External Class Import and Validation

**Input**: Design documents from `specs/002-php-interop-import/`

**Prerequisites**: plan.md ✅, spec.md ✅, research.md ✅, data-model.md ✅, contracts/ ✅

**Organization**: Tarefas agrupadas por user story para permitir implementação e validação independente de cada história.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Pode rodar em paralelo (arquivos diferentes, sem dependências pendentes)
- **[Story]**: User story correspondente (US1–US5, mapeadas da spec.md)
- Todos os caminhos de arquivo são relativos à raiz do repositório

---

## Phase 1: Setup — Sandbox Cases

**Purpose**: Criar os arquivos `.phs` adaptados dos cases de feature para os diretórios de sucesso. Remove chamadas `.display()` conforme decidido na spec; case_13 usa chaining via variável intermediária.

- [X] T001 Create `samples/success/case_39/` with `ExternalCallingConstants.phs` — adaptado de `samples/feature/case_5/ExternalCallingConstants.phs` sem `.display()`; pkg PHireScript.Samples39
- [X] T002 [P] Create `samples/success/case_40/` with `ExternalCallingChainningMethods.phs` — variável intermediária para instanciação; pkg PHireScript.Samples40
- [X] T003 [P] Create `samples/success/case_41/` with `ExternalCallingStaticMethods.phs` — pkg PHireScript.Samples41; calls `.display()` removidos

---

## Phase 2: Foundation — ExternalClassDescriptor + SymbolTable

**Purpose**: Infraestrutura central que bloqueia TODAS as user stories. Deve estar completa antes de qualquer fase de implementação.

**⚠️ CRITICAL**: Nenhuma user story pode começar antes desta fase estar completa.

- [X] T004 Create value object classes in `phirescript/src/Compiler/External/`: `ExternalMemberInfo.php`, `ExternalConstantInfo.php`, `ExternalParamInfo.php`, `ExternalConstructorInfo.php`, `ExternalPropertyInfo.php` — conforme data-model.md; cada arquivo é uma classe PHP final com propriedades readonly; usar `declare(strict_types=1)` e namespace `PHireScript\Compiler\External`
- [X] T005 Create `ExternalClassDescriptor` in `phirescript/src/Compiler/External/ExternalClassDescriptor.php` — DTO com campos `className`, `alias`, `methods` (array de ExternalMemberInfo), `constants` (array de ExternalConstantInfo), `constructor` (ExternalConstructorInfo|null), `properties` (array de ExternalPropertyInfo); imutável após construção
- [X] T006 Extend `phirescript/src/SymbolTable.php` — adicionar propriedade `private array $externals = []` e métodos `registerExternal(string $alias, ExternalClassDescriptor $descriptor): void`, `getExternal(string $alias): ?ExternalClassDescriptor`, `isExternalClass(string $name): bool`; `registerExternal` deve lançar `CompileException` se `$alias` já existe em `$typeDefinitions` (FR-014 — PHireScript native tem precedência)

**Checkpoint**: Foundation pronta — implementação das user stories pode começar.

---

## Phase 3: User Story 1 — External Declaration Validation (Priority: P1) 🎯 MVP

**Goal**: Compilador inspeciona a classe externa via Reflection ao processar `external ClassName [as Alias]`, registra o ExternalClassDescriptor no SymbolTable, e rejeita com erro quando a classe não está disponível ou conflita com classe nativa PHireScript.

**Independent Test**: Compilar um arquivo com `external DateTime as DateTimePhp` produz `use DateTime as DateTimePhp;` no PHP gerado E o ExternalClassDescriptor de `DateTime` está registrado no SymbolTable sob o alias `DateTimePhp`.

- [X] T007 [US1] Create `phirescript/src/Compiler/Binder/Declaration/ExternalBinder.php` — implementar `Binder` com `#[CompilerPass(order: 3)]`; `mustBind` retorna true para `ExternalNode`; `bind` itera `$node->namespaces`, para cada namespace: verifica `class_exists($fqcn, true)` (lança `CompileException` com mensagem de FR-008 se não existir), usa `ReflectionClass` para construir `ExternalClassDescriptor` com todos os membros públicos, chama `$binder->globalTable->registerExternal($alias, $descriptor)` (que já trata conflito de nome)
- [X] T008 [P] [US1] Create `phirescript/tests/Compiler/Binder/ExternalBinderTest.php` — testar: registro correto de `DateTime` com alias; erro quando classe não existe no autoloader (FR-008); erro quando alias conflita com classe PHireScript nativa (FR-014); descriptor contém métodos, constantes e informações do construtor corretos para `DateTime`
- [X] T009 [US1] Create `samples/success/case_39/CaseValidation.php` — esqueleto com `assertHasMessage` verificando que a compilação de case_5 produz `use DateTime as DateTimePhp;` na saída; usar package `PHireScript.Samples5` no arquivo `.phs`

**Checkpoint**: US1 completa — `external` declara, inspeciona e valida a classe corretamente.

---

## Phase 4: User Story 2 — Static vs Instance Method Resolution (Priority: P2)

**Goal**: Chamadas de método sobre class name externo usam `.` no PHireScript e o compilador emite `::` para estáticos e `(new C())->` para instância; chamadas sobre variáveis de tipo externo emitem `->`.

**Independent Test**: `DateTimePhp.createFromFormat(...)` compila para `DateTimePhp::createFromFormat(...)` e `date.format(...)` (onde `date` é `DateTime`) compila para `$date->format(...)`.

- [X] T010 [US2] Modify `phirescript/src/Compiler/Emitter/Expressions/PropertyAccessEmitter.php` — no método `emit`, verificar se `$node->object` é um identificador de classe externa (via `$ctx->symbolTable->isExternalClass($name)`); se sim, consultar descriptor via `getExternal()`: constante → emitir `ClassName::NAME`, método estático → emitir `ClassName::method(...)`, método instância em class name → emitir `(new ClassName())->method(...)`; para variáveis com tipo externo inferido (`getType($varName)` retorna `ExternalClassDescriptor`) → emitir `$var->property` ou `$var->method(...)`
- [X] T011 [US2] Create `phirescript/src/Compiler/Checker/Declaration/External/ExternalMemberAccessChecker.php` — implementar `Checker` com `#[CompilerPass(order: 5)]`; verificar chamadas de método sobre class names externos e variáveis de tipo externo: lança `CompileException` se método não existe no descriptor (FR-006), lança `CompileException` se membro não é público (FR-007)
- [X] T012 [P] [US2] Create `phirescript/tests/Compiler/Checker/ExternalMemberAccessCheckerTest.php` — testar: método estático válido passa; método instância válido passa; método inexistente lança CompileException (FR-006); método não-público lança CompileException (FR-007)
- [X] T013 [P] [US2] Extend `phirescript/tests/Compiler/Emitter/Expressions/PropertyAccessEmitterTest.php` — adicionar testes para emissão de `::` em método estático externo, `(new C())->` em método instância em class name, `$var->` em método instância em variável de tipo externo; garantir que testes existentes (non-external) continuam passando
- [X] T014 [US2] Update `samples/success/case_15/CaseValidation.php` — adicionar assertions para: `PDO::getAvailableDrivers()` (estático) e `(new PDO())->query(...)` (instância em class name)

**Checkpoint**: US2 completa — static vs instance resolvidos corretamente no output PHP.

---

## Phase 5: User Story 3 — Constant Access (Priority: P2)

**Goal**: `DateTimePhp.ATOM` compila para `DateTimePhp::ATOM`; constante inexistente ou não-pública gera erro de compilação.

**Independent Test**: Compilar `date.format(DateTimePhp.ATOM)` produz `$date->format(DateTimePhp::ATOM)` no PHP gerado.

- [X] T015 [US3] Extend `phirescript/src/Compiler/Emitter/Expressions/PropertyAccessEmitter.php` — quando `$node->property` é string maiúscula e o objeto é um class name externo, verificar `ExternalClassDescriptor::$constants`; se encontrado, emitir `ClassName::CONSTANT` em vez de `ClassName->CONSTANT`; prioridade de lookup: constante > método estático > método instância
- [X] T016 [P] [US3] Extend `phirescript/src/Compiler/Checker/Declaration/External/ExternalMemberAccessChecker.php` — adicionar validação de acesso a constantes: lança `CompileException` se constante não existe no descriptor (FR-006), lança `CompileException` se constante não é pública (FR-007)
- [X] T017 [US3] Update `samples/success/case_5/CaseValidation.php` — adicionar assertion para acesso à constante `DateTimePhp::ATOM` no output PHP

**Checkpoint**: US3 completa — constantes externas acessíveis e validadas.

---

## Phase 6: User Story 4 — External Instantiation + Method Chaining (Priority: P3)

**Goal**: `DateTimePhp()` compila para `new DateTimePhp()`; Checker valida construtor (visibilidade + args obrigatórios); chaining funciona sobre variável intermediária.

**Independent Test**: Compilar `date = DateTimePhp()` produz `$date = new DateTimePhp();`; construtor não-público ou args obrigatórios ausentes produz erro de compilação.

- [X] T018 [US4] Add `public bool $isExternalInstantiation = false` to `phirescript/src/Compiler/Parser/Ast/Nodes/Declarations/FunctionNode.php`
- [X] T019 [US4] Extend `phirescript/src/Compiler/Binder/Declaration/ExternalBinder.php` — no `bind`, após registrar o descriptor, percorrer `Program::$statements` em busca de `AssignmentNode` cujo `right` é `FunctionNode` com nome correspondente a um alias external; quando encontrado, setar `$functionNode->isExternalInstantiation = true`
- [X] T020 [US4] Create `phirescript/src/Compiler/Checker/Declaration/External/ExternalInstantiationChecker.php` — implementar `Checker` com `#[CompilerPass(order: 6)]`; `mustCheck` retorna true para `FunctionNode` com `isExternalInstantiation = true`; valida: construtor existe e é público (FR-009 / construtor privado), parâmetros obrigatórios fornecidos (FR-009 / args ausentes)
- [X] T021 [US4] Create `phirescript/src/Compiler/Emitter/Declarations/ExternalCallEmitter.php` — implementar `NodeEmitter`; `supports` retorna true para `FunctionNode` com `isExternalInstantiation = true`; `emit` produz `new ClassName(args)` com argumentos emitidos pelo emitter principal
- [X] T022 [US4] Register `ExternalCallEmitter` in `phirescript/src/Compiler/Emitter.php` — adicionar `new ExternalCallEmitter()` ao array de emitters (antes do FunctionEmitter para que seja resolvido primeiro)
- [X] T023 [P] [US4] Create `phirescript/tests/Compiler/Checker/ExternalInstantiationCheckerTest.php` — testar: construtor público sem args passa; construtor público com args obrigatórios ausentes lança CompileException; construtor privado lança CompileException
- [X] T024 [P] [US4] Create `phirescript/tests/Compiler/Emitter/Declarations/ExternalCallEmitterTest.php` — testar: `DateTimePhp()` emite `new DateTimePhp()`; `DateTimePhp('2023-12-25')` emite `new DateTimePhp('2023-12-25')`
- [X] T025 [US4] Create `samples/success/case_13/CaseValidation.php` — assertions: `new DateTimePhp()` presente no output; chaining `->modify('+3 days')->modify('+2 hours')->format(...)` presente; output final compatível com `samples/feature/case_13/ExternalCallingChainningMethods.phc` (exceto `.display()`)

**Checkpoint**: US4 completa — instanciação e chaining via variável funcionando.

---

## Phase 7: User Story 5 — Return Type Propagation + Cascaded Validation (Priority: P3)

**Goal**: Variável atribuída de chamada de método externo recebe tipo inferido no SymbolTable; chamadas subsequentes sobre ela são validadas; `mixed`/union type geram warnings.

**Independent Test**: Compilar `query = PDO.query(...)` seguido de `user = query.fetchObject()` — o compilador valida que `fetchObject` existe em `PDOStatement` e emite `$query->fetchObject()`.

- [X] T026 [US5] Extend `phirescript/src/Compiler/Binder/Declaration/ExternalBinder.php` — ao processar `AssignmentNode` cujo `right` envolve acesso a membro externo (PropertyAccessNode sobre external), extrair o tipo de retorno do membro via `ExternalClassDescriptor::$methods[$name]->returnType`; chamar `$binder->globalTable->setType($varName, $returnDescriptor)` onde `$returnDescriptor` é: `ExternalClassDescriptor` resolvido para o tipo de retorno, `string[]` para union types, ou `'MIXED_EXTERNAL'` quando `null`
- [X] T027 [US5] Extend `phirescript/src/Compiler/Checker/Declaration/External/ExternalMemberAccessChecker.php` — quando `getType($varName)` retorna um `ExternalClassDescriptor` (tipo propagado), validar o membro acessado contra esse descriptor (mesma lógica de FR-006/FR-007 usada para class names diretos)
- [X] T028 [US5] Add warning paths to `phirescript/src/Compiler/Checker/Declaration/External/ExternalMemberAccessChecker.php` — FR-015: quando `getType($varName)` retorna `'MIXED_EXTERNAL'`, emitir warning via `Messenger` e não validar membros; FR-012: quando tipo é `string[]` (union) e método acessado existe apenas em subconjunto dos tipos, emitir warning
- [X] T029 [US5] Update `samples/success/case_15/CaseValidation.php` — adicionar assertions para: tipo de `query` inferido como `PDOStatement|false`; `$query->fetchObject()` no output; propriedades `$user->id`, `$user->name`, `$user->email` no output; warnings esperados (union type, PDO constructor args) listados em `assertHasMessage`

**Checkpoint**: US5 completa — todas as user stories funcionando end-to-end.

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: Qualidade, conformidade com ferramentas do projeto e atualização de documentação.

- [X] T030 [P] Run `composer quality` in `phirescript/` and fix all PHPStan level 9, php-cs-fixer, and rector violations introduced by the new classes — todos os arquivos novos devem passar sem supressões
- [X] T031 [P] Update `phirescript/CLAUDE.md` — mover `external declarations` de "Partial" para "Functional" na seção "Language Feature Status"; atualizar descrição para refletir validação via Reflection, static/instance/const resolution e type propagation
- [X] T032 Run `php bin/stretch --mode=success` from sandbox root — verificar que case_5, case_13 e case_15 passam 100%; corrigir quaisquer falhas de compilação ou assertion restantes

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Sem dependências — pode começar imediatamente; T002 e T003 paralelos com T001
- **Foundation (Phase 2)**: Depende de Phase 1 (caminhos dos cases precisam existir para testes de integração); T005 depende de T004; T006 depende de T005
- **US1 (Phase 3)**: Depende da Foundation completa (T004–T006); T008 paralelo com T007
- **US2 (Phase 4)**: Depende de US1 completa (ExternalBinder deve estar registrado); T012 e T013 paralelos
- **US3 (Phase 5)**: Depende de US2 (compartilha PropertyAccessEmitter e ExternalMemberAccessChecker); T016 paralelo com T015
- **US4 (Phase 6)**: Depende de US1 (ExternalBinder base); independente de US2/US3; T023 e T024 paralelos
- **US5 (Phase 7)**: Depende de US2 (ExternalMemberAccessChecker já existe); depende de US4 (type propagation precisa de instanciação funcional)
- **Polish (Phase 8)**: Depende de todas as user stories; T030 e T031 paralelos

### User Story Dependencies

- **US1 (P1)**: Pode começar após Foundation — sem dependência de outras stories
- **US2 (P2)**: Depende de US1 (ExternalBinder popula SymbolTable que o Checker consulta)
- **US3 (P2)**: Depende de US2 (compartilha os mesmos arquivos de Emitter e Checker)
- **US4 (P3)**: Depende de US1 apenas; pode ser implementada em paralelo com US2/US3
- **US5 (P3)**: Depende de US2 (Checker de membros) e US4 (instanciação funcional)

### Within Each User Story

- Modelos/DTOs antes de Binder/Checker/Emitter
- Binder antes de Checker (Checker consulta o SymbolTable que o Binder popula)
- Checker antes de Emitter (validação antes de emissão)
- Sandbox CaseValidation por último (valida a story completa end-to-end)

---

## Parallel Opportunities

### Phase 1 — Setup

```
T001 (case_5 .phs) ──┐
T002 (case_13 .phs) ──┤ todos em paralelo
T003 (case_15 .phs) ──┘
```

### Phase 3 — US1

```
T007 (ExternalBinder impl)
T008 (ExternalBinderTest) ── paralelo com T007 (arquivo diferente)
T009 (case_5 CaseValidation) ── após T007
```

### Phase 4 — US2

```
T010 (PropertyAccessEmitter) ──┐
T011 (ExternalMemberAccessChecker) ──┤ sequencial (T011 pode referenciar T010)
T012 (ExternalMemberAccessCheckerTest) ──┤ paralelo com T011
T013 (PropertyAccessEmitterTest extension) ──┘ paralelo com T010
T014 (case_15 CaseValidation) ── após T010 + T011
```

### Phase 6 — US4

```
T018 (FunctionNode flag) ──┐
T019 (ExternalBinder extension) ── após T018
T020 (ExternalInstantiationChecker) ──┐
T021 (ExternalCallEmitter) ──────────┤ paralelos entre si (após T018/T019)
T022 (Emitter.php registration) ── após T021
T023 (InstantiationCheckerTest) ── paralelo com T020
T024 (ExternalCallEmitterTest) ── paralelo com T021
T025 (case_13 CaseValidation) ── após T020 + T021 + T022
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup (T001–T003)
2. Complete Phase 2: Foundation (T004–T006)
3. Complete Phase 3: US1 (T007–T009)
4. **STOP e VALIDE**: `php bin/stretch --mode=success` no case_5 (apenas declaração)
5. A declaração `external` com Reflection está funcional

### Incremental Delivery

1. Setup + Foundation → base pronta
2. US1 → Reflection + erros de compilação → MVP validado
3. US2 + US3 → static/instance/const → case_5 e case_15 parcialmente validados
4. US4 → instanciação → case_13 validado
5. US5 → type propagation → case_15 totalmente validado
6. Polish → qualidade + CLAUDE.md atualizado

---

## Notes

- `[P]` = arquivo diferente, sem dependências pendentes — pode rodar em paralelo
- `[Story]` = rastreabilidade para user story da spec
- Cada user story é independentemente compilável e testável via sandbox case
- Nunca commitar código com PHPStan level 9 falhando
- Só mover case para `samples/success/` quando CaseValidation.php passa 100% no orchestrator
- `display()` foi explicitamente excluído do escopo — não implementar nem validar
