# Implementation Plan: PHP Interop — External Class Import and Validation

**Branch**: `002-php-interop-import` | **Date**: 2026-05-30 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `specs/002-php-interop-import/spec.md`

---

## Summary

Implementar suporte completo ao interop com classes PHP externas no compilador PHireScript: a declaração `external ClassName [as Alias]` já gera `use` corretamente, mas o compilador não inspeciona a classe, não distingue static de instância, não valida membros, não propaga tipos de retorno e não instancia classes com `ClassName()`. Esta feature completa toda essa cadeia via Reflection (ReflectionClass), estendendo o Binder (registro de ExternalClassDescriptor no SymbolTable), o Checker (validação de acessibilidade, membros, construtores) e o Emitter (emissão correta de `::` vs `->` vs `new`).

---

## Technical Context

**Language/Version**: PHP 8.2 (compilador) gerando PHP 8.2+ (output)

**Primary Dependencies**: `nikic/php-parser` (já em uso), `ReflectionClass` nativo do PHP, `Composer autoloader` (presente em ambos os projetos)

**Storage**: SymbolTable in-memory por sessão de compilação; sem persistência em disco nesta feature

**Testing**: PHPUnit (testes unitários no `phirescript/tests/`); orquestrador sandbox (`php bin/stretch --mode=success`) para validação de integração via `CaseValidation.php`

**Target Platform**: PHP 8.2+, CLI

**Project Type**: Compilador/transpiler

**Performance Goals**: Reflection de cada classe externa no máximo uma vez por compilação (cache in-memory no SymbolTable); erros de compilação em < 1 segundo

**Constraints**: Classes externas devem estar disponíveis no autoloader do ambiente de compilação; sem persistência de cache entre sessões nesta versão

**Scale/Scope**: 3 sandbox cases (5, 13, 15) + testes unitários por fase do pipeline

---

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

A constituição do projeto não foi preenchida. Usando como referência o `CLAUDE.md` do compilador:

- **Pipeline order**: todas as mudanças seguem a ordem Scanner → Parser → Binder → Checker → Emitter. Nenhuma fase salta outra. ✅
- **PHPStan level 9**: todo código novo deve passar sem supressões. ✅ (obrigatório — gates de qualidade já existem)
- **Um commit por feature completa**: só commitar quando o case de sandbox passa. ✅
- **Convenção de Commits**: Conventional Commits em inglês, sem `Co-Authored-By`. ✅

Sem violações identificadas. Complexidade justificada: a feature adiciona 3 novas classes de Binder/Checker/Emitter seguindo os padrões existentes (ClassBinder, ClassChecker, PropertyAccessEmitter como referências).

---

## Project Structure

### Documentation (this feature)

```text
specs/002-php-interop-import/
├── plan.md              # Este arquivo
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── contracts/           # Phase 1 output
└── tasks.md             # Phase 2 output (/speckit-tasks)
```

### Source Code — arquivos novos a criar

```text
phirescript/src/
├── Compiler/
│   ├── Binder/
│   │   └── Declaration/
│   │       └── ExternalBinder.php                  # registra ExternalClassDescriptor no SymbolTable
│   ├── Checker/
│   │   └── Declaration/
│   │       └── External/
│   │           ├── ExternalMemberAccessChecker.php  # valida acesso a métodos/constantes/construtor
│   │           └── ExternalInstantiationChecker.php # valida ClassName() — construtor público + args
│   └── Emitter/
│       └── Declarations/
│           └── ExternalCallEmitter.php              # emite ::method(), (new C())->method(), new C(), ::CONST
└── ExternalClassDescriptor.php                      # DTO com mapa de membros inspecionados via Reflection
```

### Source Code — arquivos existentes a modificar

```text
phirescript/src/
├── SymbolTable.php                                   # + registerExternal(), getExternal(), isExternalClass()
├── Compiler/Binder/Root/TypeRegistrationBinder.php  # + registrar ExternalNode via ExternalBinder
├── Compiler/Emitter/Expressions/PropertyAccessEmitter.php  # + detectar quando objeto é external e delegar
└── Compiler/Emitter/Declarations/ExternalEmitter.php        # existente — sem mudança (apenas emite `use`)

phirescript/tests/
├── Compiler/Binder/ExternalBinderTest.php
├── Compiler/Checker/ExternalMemberAccessCheckerTest.php
├── Compiler/Checker/ExternalInstantiationCheckerTest.php
└── Compiler/Emitter/Declarations/ExternalCallEmitterTest.php

PHire-Script-Sandbox/samples/success/
├── case_5/   # ExternalCallingConstants
├── case_13/  # ExternalCallingChainningMethods (via variável)
└── case_15/  # ExternalCallingStaticMethods
```

**Structure Decision**: Single-project, compilador PHP puro, sem frontend. Segue estrutura existente de Binder/Checker/Emitter com subpastas por domínio.

---

## Phase 0: Research

### Decisão 1 — Como o Emitter distingue chamada estática vs instância

**Decisão**: O `ExternalBinder` popula o `ExternalClassDescriptor` com o mapa de membros (método estático / instância / constante) via `ReflectionClass`. O `Emitter` consulta o SymbolTable para obter o descriptor e decide o output. Não existe heurística sintática — a informação vem exclusivamente de Reflection.

**Rationale**: Único mecanismo confiável. Qualquer heurística (ALL_CAPS = constante, etc.) cria regras implícitas de linguagem que conflitam com código PHP real.

**Alternativas descartadas**: Convenção de nomes (frágil); anotação manual no `.phs` (verboso, contradiz o princípio de transparência da linguagem).

---

### Decisão 2 — Onde o ExternalClassDescriptor vive e quem o cria

**Decisão**: O `ExternalBinder` é responsável pela criação do `ExternalClassDescriptor` via Reflection durante a fase de Binding. O descriptor é registrado no `SymbolTable` via `registerExternal(string $alias, ExternalClassDescriptor $descriptor)`. Tanto o Checker quanto o Emitter consultam via `getExternal(string $alias)`.

**Rationale**: O Binder já é a fase onde ClassNode e InterfaceNode são registrados no SymbolTable (`TypeRegistrationBinder`). Seguir o mesmo padrão mantém coerência do pipeline.

**Alternativas descartadas**: Criar o descriptor no Parser (acoplamento premature entre parsing e Reflection); criar no Checker (duplicaria responsabilidade; Checker deve validar, não descobrir).

---

### Decisão 3 — Como o PropertyAccessEmitter detecta acesso a external

**Decisão**: O `PropertyAccessEmitter` verifica se o objeto do `PropertyAccessNode` é um `VariableNode` cujo tipo no SymbolTable é um `ExternalClassDescriptor`. Se sim, consulta o descriptor para decidir entre `->property`, `::CONSTANT` ou `::method()` / `->method()`. Se o objeto é um class name diretamente (não variável), o mesmo lookup é feito pelo nome da classe.

**Rationale**: `PropertyAccessEmitter` já é o ponto centralizado para emissão de `.` → `->`. Estendê-lo com a detecção de external é menos invasivo do que criar um emitter separado que duplica a lógica de acesso.

**Alternativas descartadas**: Novo emitter `ExternalPropertyAccessEmitter` exclusivo (mais clean, mas exige modificar o dispatcher e duplicar lógica de traversal).

---

### Decisão 4 — Como ClassName() (instanciação) é parseada

**Decisão**: O parser já reconhece `Identifier()` como uma chamada de função/método (via `FunctionCallResolver`). Para externals, `ClassName()` deve gerar um novo nó `ExternalInstantiationNode` (ou ser identificado no Binder como instanciação) para que o Emitter possa emitir `new ClassName(args)`. O Checker valida o construtor antes da emissão.

**Rationale**: Separar a instanciação de external (`new`) de uma chamada de método comum (`::method()` ou `->method()`) no nível do AST evita lógica condicional espalhada no Emitter.

**Alternativas descartadas**: Reusar FunctionNode com flag `isInstantiation` (funciona, mas polui FunctionNode com semântica de instanciação).

---

### Decisão 5 — Propagação de tipo de retorno no SymbolTable

**Decisão**: Quando o Binder processa um `AssignmentNode` cujo lado direito é um `ExternalMemberCall` (método com tipo de retorno conhecido via Reflection), o Binder registra no SymbolTable: `setType($varName, $returnTypeDescriptor)`. O Checker, ao processar chamadas subsequentes sobre essa variável, faz lookup do tipo e valida contra o descriptor correspondente.

**Rationale**: O `SymbolTable.setType()` / `getType()` já existe e é usado. Estender com tipos externos segue o padrão estabelecido.

**Casos especiais**:
- Retorno `mixed` ou sem tipo: `setType($var, 'MIXED_EXTERNAL')` → Checker emite warning, não valida chamadas
- Retorno union type (`A|B`): `setType($var, ['A', 'B'])` → Checker valida contra todos os tipos, warning se método só existe em subconjunto

---

## Phase 1: Design & Contracts

### Data Model — ExternalClassDescriptor

Ver `data-model.md` para definição completa.

Resumo:
- `$className: string` — FQCN original
- `$alias: string` — nome usado no PHireScript
- `$methods: array<string, ExternalMemberInfo>` — métodos públicos (static/instance + tipo de retorno)
- `$constants: array<string, ExternalConstantInfo>` — constantes públicas
- `$constructor: ?ExternalConstructorInfo` — visibilidade + parâmetros obrigatórios
- `$properties: array<string, ExternalPropertyInfo>` — propriedades públicas (sem validação nesta versão, apenas compilação)

### Interface Contracts

Ver `contracts/external-binder-contract.md` para o contrato formal do ExternalBinder.

Resumo das transformações esperadas:

| PHireScript | PHP gerado | Condição |
|-------------|-----------|----------|
| `external DateTime as DateTimePhp` | `use DateTime as DateTimePhp;` | sempre |
| `DateTimePhp.createFromFormat(...)` | `DateTimePhp::createFromFormat(...)` | método estático |
| `DateTimePhp.ATOM` | `DateTimePhp::ATOM` | constante |
| `PDO.getAvailableDrivers()` | `PDO::getAvailableDrivers()` | método estático |
| `PDO.query(...)` | `(new PDO())->query(...)` | método instância chamado em class name |
| `DateTimePhp()` | `new DateTimePhp()` | instanciação |
| `date.modify(...)` onde `date` é instância de DateTimePhp | `$date->modify(...)` | método instância em variável |
| `user.id` onde `user` é tipo externo | `$user->id` | propriedade (sem validação) |

### Pipeline de Execução por Fase

```
external DateTime as DateTimePhp
         │
    [Scanner] → token 'external' já reconhecido ✅
         │
    [Parser/ExternalContext] → ExternalNode{namespaces:[{namespace:'DateTime', alias:'DateTimePhp'}]} ✅
         │
    [Binder/ExternalBinder] → ReflectionClass('DateTime')
         │                  → ExternalClassDescriptor{alias:'DateTimePhp', methods:[...], constants:[...]}
         │                  → SymbolTable::registerExternal('DateTimePhp', descriptor) NOVO
         │
    [Checker/ExternalMemberAccessChecker] → valida cada acesso a membro do descriptor NOVO
    [Checker/ExternalInstantiationChecker] → valida ClassName() — construtor + args NOVO
         │
    [Emitter/PropertyAccessEmitter] → detecta external → emite :: ou -> MODIFICADO
    [Emitter/ExternalCallEmitter]   → emite new ClassName() NOVO
         │
    [PhpFileGenerator] → output final ✅
```

---

## Complexity Tracking

Sem violações de constituição. Complexidade inerente à feature:

| Aspecto | Complexidade | Justificativa |
|---------|-------------|---------------|
| ReflectionClass em compile-time | Média | Necessário; cacheado por classe no SymbolTable |
| Propagação de tipo de retorno | Alta | Requer extensão do SymbolTable e Checker |
| Distinção static/instance/const no Emitter | Média | Centralizado no PropertyAccessEmitter via descriptor |
| Parser: ClassName() como instanciação | Média | Novo nó ou flag no Binder para distinguir de FunctionCall |
