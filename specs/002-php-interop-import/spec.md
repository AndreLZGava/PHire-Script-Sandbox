# Feature Specification: PHP Interop — External Class Import and Validation

**Feature Branch**: `002-php-interop-import`

**Created**: 2026-05-30

**Status**: Draft

**Input**: User description: "considerando os cases 5, 13 e 15 dos samples/features — importar código PHP pronto para dentro do PHireScript, garantir que o código PHP pode ser desenvolvido seguindo o padrão PHireScript, compilar corretamente, e validar que o código PHP usado está realmente correto (propriedades acessíveis, métodos acessíveis, static vs non-static, constantes)."

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Declarar e usar uma classe PHP externa com alias (Priority: P1)

O desenvolvedor importa uma classe PHP existente (ex: `DateTime`, `PDO`, qualquer biblioteca Composer) usando a palavra-chave `external`, opcionalmente atribuindo um alias, e passa a usá-la no código PHireScript como se fosse uma classe nativa.

**Why this priority**: É o bloco fundamental de toda a feature. Sem a declaração `external` funcionando, nenhuma das histórias seguintes é possível.

**Independent Test**: Pode ser testado declarando `external DateTime as DateTimePhp`, compilando, e verificando que o output PHP contém `use DateTime as DateTimePhp;` — sem precisar que nenhuma chamada de método funcione.

**Acceptance Scenarios**:

1. **Given** um arquivo `.phs` com `external DateTime as DateTimePhp`, **When** o compilador processa o arquivo, **Then** o output PHP contém `use DateTime as DateTimePhp;` na posição correta.
2. **Given** um arquivo `.phs` com `external PDO` (sem alias), **When** compilado, **Then** o output contém `use PDO;`.
3. **Given** `external` com um namespace completo como `external Symfony\Component\HttpFoundation\Request`, **When** compilado, **Then** o output contém `use Symfony\Component\HttpFoundation\Request;`.
4. **Given** a classe externa não está disponível via autoloader, **When** compilado, **Then** o compilador emite um erro de compilação identificando que a classe não pode ser carregada para validação.

---

### User Story 2 — Chamar métodos estáticos e de instância com sintaxe unificada (Priority: P2)

O desenvolvedor chama métodos usando `.` para qualquer tipo de chamada (estática ou de instância). O compilador resolve automaticamente qual forma usar no PHP gerado — `::` para estáticos e `->` para instância — usando Reflection sobre a classe externa.

**Why this priority**: É a característica central do PHireScript para externals — a transparência entre chamadas estáticas e de instância. Sem isso, o código gerado está errado.

**Independent Test**: Pode ser testado com `external DateTime`, chamando `DateTime.createFromFormat(...)` e verificando que o output usa `DateTime::createFromFormat(...)`.

**Acceptance Scenarios**:

1. **Given** `external DateTime as DateTimePhp` e o código `date = DateTimePhp.createFromFormat('d/m/Y', '25/12/2023')`, **When** compilado, **Then** o output é `$date = DateTimePhp::createFromFormat('d/m/Y', '25/12/2023');`.
2. **Given** `external PDO` e `availableDrivers = PDO.getAvailableDrivers()`, **When** compilado, **Then** o output é `$availableDrivers = PDO::getAvailableDrivers();`.
3. **Given** `external PDO` e `query = PDO.query("SELECT 1")`, **When** compilado, **Then** o output é `$query = (new PDO())->query("SELECT 1");`.
4. **Given** o método chamado não existe na classe externa, **When** compilado, **Then** o compilador emite um erro de compilação com nome do método e classe.
5. **Given** o método existe mas não é público, **When** compilado, **Then** o compilador emite um erro informando que o membro não é acessível.

---

### User Story 3 — Acessar constantes de classes externas (Priority: P2)

O desenvolvedor acessa constantes de uma classe externa com a mesma sintaxe de acesso de propriedade (`.`). O compilador resolve para `::` no PHP gerado.

**Why this priority**: Constantes são parte do contrato público de muitas classes PHP (`DateTime::ATOM`, `PDO::FETCH_OBJ`, etc.). Não suportá-las limita o uso prático da feature.

**Independent Test**: Pode ser testado com `DateTimePhp.ATOM` verificando que o output é `DateTimePhp::ATOM`.

**Acceptance Scenarios**:

1. **Given** `external DateTime as DateTimePhp` e o código `date.format(DateTimePhp.ATOM)`, **When** compilado, **Then** o output contém `$date->format(DateTimePhp::ATOM)`.
2. **Given** a constante acessada não existe na classe, **When** compilado, **Then** o compilador emite um erro de compilação.
3. **Given** a constante existe mas não é pública, **When** compilado, **Then** o compilador emite um erro de acessibilidade.

---

### User Story 4 — Instanciar classe externa com `()` e encadear métodos (Priority: P3)

O desenvolvedor instancia uma classe externa usando `ClassName()` e encadeia chamadas de método sobre a instância resultante, primeiro atribuindo a uma variável e depois chamando métodos sobre ela.

**Why this priority**: O encadeamento direto sobre o valor não-declarado (`DateTimePhp().modify(...)`) é mais complexo de implementar e pode ser postergado. O caso imediato — instanciar e depois encadear sobre uma variável — é suficiente para a feature ser funcional.

**Independent Test**: Pode ser testado com `date = DateTimePhp()` seguido de `date.modify('+3 days')`, verificando que `DateTimePhp()` compila para `new DateTimePhp()` e `date.modify(...)` compila para `$date->modify(...)`.

**Acceptance Scenarios**:

1. **Given** `date = DateTimePhp()`, **When** compilado, **Then** o output é `$date = new DateTimePhp();`.
2. **Given** `date = DateTimePhp()` seguido de `date.modify('+3 days').modify('+2 hours').format('d/m/Y H:i')` atribuído a uma nova variável, **When** compilado, **Then** o output encadeia com `->`.
3. **Given** a classe externa tem construtor com parâmetros obrigatórios e é instanciada sem argumentos, **When** compilado, **Then** o compilador emite um erro informando os parâmetros obrigatórios ausentes.
4. **Given** encadeamento multi-linha iniciando diretamente sobre o valor retornado (sem variável intermediária), **When** o desenvolvedor tenta compilar, **Then** o compilador emite um erro ou aviso indicando que o encadeamento direto sobre valores não declarados não é suportado nesta versão — recomendando declarar a variável primeiro.

---

### User Story 5 — Propagar tipo de retorno e validar chamadas em cascata (Priority: P3)

Uma variável atribuída a partir do retorno de um método externo recebe o tipo inferido via Reflection. Chamadas subsequentes sobre essa variável são validadas contra esse tipo inferido.

**Why this priority**: Completa o contrato de validação end-to-end. Sem isso, um erro como chamar `fetchObject()` em um valor que não é `PDOStatement` passaria silenciosamente.

**Independent Test**: Pode ser testado com `query = PDO.query(...)` seguido de `user = query.fetchObject()` — o compilador deve saber que `query` é `PDOStatement` e validar que `fetchObject()` existe nele.

**Acceptance Scenarios**:

1. **Given** `query = PDO.query("SELECT 1")` onde `PDO::query()` retorna `PDOStatement|false`, **When** o código seguinte chama `query.fetchObject()`, **Then** o compilador valida que `fetchObject` existe em `PDOStatement` e compila para `$query->fetchObject()`.
2. **Given** `query = PDO.query(...)` e o código subsequente chama `query.metodoInexistente()`, **When** compilado, **Then** o compilador emite um erro apontando que `metodoInexistente` não existe em `PDOStatement`.
3. **Given** o tipo de retorno é uma union type (`PDOStatement|false`), **When** o código acessa um método que existe apenas em `PDOStatement`, **Then** o compilador emite um aviso de que o acesso pode ser inválido dependendo do valor em runtime.

---

### Edge Cases

- **Resolvido**: Se `external ClassName` sem alias conflitar com uma classe PHireScript nativa de mesmo nome, o compilador emite erro exigindo alias. Classes PHireScript têm precedência; o alias é obrigatório para interoperabilidade com classes PHP de mesmo nome.
- **Resolvido**: Construtor privado ou protegido em classe externa — o Checker emite erro de compilação ao detectar `ClassName()` com construtor não-público. O Parser constrói a árvore normalmente; a validação de acessibilidade é responsabilidade do Checker.
- **Resolvido**: Se o método externo retornar `mixed` ou não tiver tipo de retorno declarado, o compilador emite warning informando que chamadas subsequentes sobre a variável não serão validadas. A compilação prossegue normalmente.
- O que acontece se `external` for declarado dentro de uma classe ou método, e não no nível raiz do arquivo?
- O que ocorre quando a mesma classe externa é importada com dois aliases diferentes em arquivos distintos que se importam mutuamente?

---

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: O compilador DEVE reconhecer e processar a declaração `external ClassName` e `external ClassName as Alias`, emitindo o `use` PHP correspondente.
- **FR-002**: O compilador DEVE usar Reflection (`ReflectionClass`) para inspecionar a classe externa em tempo de compilação, determinando quais membros são estáticos, de instância, constantes ou propriedades, e sua visibilidade.
- **FR-003**: O compilador DEVE emitir `ClassName::method()` para chamadas de métodos estáticos e `(new ClassName())->method()` para chamadas de métodos de instância feitas diretamente sobre o class name.
- **FR-004**: O compilador DEVE emitir `ClassName::CONSTANT` para acesso a constantes de classes externas.
- **FR-005**: O compilador DEVE emitir `new ClassName()` quando a classe externa é instanciada com `ClassName()`.
- **FR-006**: O compilador DEVE emitir erro de compilação quando um método, propriedade ou constante acessado não existe na classe externa.
- **FR-007**: O compilador DEVE emitir erro de compilação quando um membro existe mas não é público (protegido ou privado).
- **FR-008**: O compilador DEVE emitir erro de compilação quando a classe externa não está disponível via autoloader no momento da compilação.
- **FR-014**: O compilador DEVE emitir erro de compilação quando `external ClassName` (sem alias) é declarado e existe uma classe PHireScript nativa com o mesmo nome no projeto, exigindo que o desenvolvedor use `external ClassName as Alias` para resolver o conflito. Classes PHireScript têm precedência sobre classes externas de mesmo nome.
- **FR-009**: O Checker DEVE emitir erro de compilação quando uma classe externa é instanciada com `ClassName()` sem fornecer os argumentos obrigatórios do construtor, ou quando o construtor não é público.
- **FR-010**: O compilador DEVE registrar no SymbolTable o tipo inferido de variáveis atribuídas a partir de chamadas de métodos externos (usando o tipo de retorno declarado via Reflection).
- **FR-011**: O compilador DEVE validar chamadas de método em variáveis cujo tipo foi inferido de um método externo, usando o tipo de retorno registrado.
- **FR-012**: O compilador DEVE emitir aviso (warning, não erro) quando o tipo de retorno inferido é uma union type e o método acessado existe apenas em parte dos tipos da union.
- **FR-015**: O compilador DEVE emitir aviso (warning, não erro) quando o tipo de retorno de um método externo é `mixed` ou não está declarado — informando que chamadas subsequentes sobre a variável resultante não serão validadas. A compilação prossegue normalmente.
- **FR-013**: O encadeamento multi-linha sobre o valor direto de `ClassName()` (sem variável intermediária) NÃO é suportado nesta versão; o compilador DEVE emitir mensagem clara recomendando declarar a variável antes de encadear.

### Key Entities

- **ExternalDeclaration**: A declaração `external ClassName [as Alias]` — possui classe de origem e alias opcional.
- **ExternalClassDescriptor**: Representação em memória (SymbolTable) da classe externa inspecionada via Reflection — contém mapa de métodos (estático/instância/visibilidade), constantes, propriedades e tipos de retorno.
- **ExternalMemberCall**: Uma chamada de membro (método, constante, propriedade) sobre um class name externo ou sobre uma variável de tipo externo inferido.

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Os cases 5, 13 e 15 de `samples/feature/` compilam sem erros e produzem output PHP idêntico aos respectivos `.phc` (exceto as chamadas `.display()` que são removidas ou tratadas separadamente).
- **SC-002**: Um case de validação (`CaseValidation.php`) para cada um dos três cases passa 100% no orchestrator (`php bin/stretch --mode=success`).
- **SC-003**: Chamadas a métodos inexistentes em classes externas produzem erro de compilação em menos de 1 segundo.
- **SC-004**: A inspeção Reflection de cada classe externa ocorre no máximo uma vez por compilação (resultado cacheado no SymbolTable da sessão).
- **SC-005**: O compilador rejeita corretamente pelo menos os seguintes cenários inválidos: método inexistente, membro não-público, classe não carregada, construtor com args obrigatórios omitidos.
- **SC-006**: O tipo de retorno de chamadas externas é propagado corretamente para variáveis, permitindo validação de chamadas em cascata (conforme case 15: `query` → `PDOStatement` → `fetchObject()`).

---

## Assumptions

- A classe PHP externa deve estar disponível via Composer/autoloader no ambiente de compilação; classes não-carregadas causam erro — isso é comportamento esperado e documentado.
- A sintaxe `external` permanece exclusiva ao nível raiz do arquivo (não dentro de classes ou funções); este escopo não é expandido nesta feature.
- O encadeamento direto sobre `ClassName()` sem variável intermediária (ex: `DateTimePhp().modify(...)` em uma única expressão) fica fora de escopo; o desenvolvedor deve primeiro atribuir a uma variável.
- `display()` e outros métodos built-in do PHireScript sobre valores retornados de externos ficam fora de escopo desta feature. Se os cases de exemplo usarem `.display()`, esses calls serão removidos dos casos de validação, ou tratados como feature futura separada.
- O compilador usa `ReflectionClass` do PHP para inspeção, portanto o ambiente de compilação precisa ter as dependências das classes externas carregadas (Composer autoload ativo).
- Union types (`PDOStatement|false`) são tratados com warning e não com erro; a responsabilidade de garantir o tipo correto em runtime é do desenvolvedor.
- Propriedades de instância de classes externas (ex: `user.id`, `user.name`) são compiladas como `$user->id`, `$user->name` sem validação de existência nesta versão — apenas métodos e constantes são validados via Reflection neste escopo.

---

## Clarifications

### Session 2026-05-30

- Q: Qual é a regra de precedência quando `external ClassName` conflita com uma classe PHireScript nativa de mesmo nome? → A: Classes PHireScript têm precedência. `external ClassName` sem alias quando há conflito de nome gera erro de compilação, exigindo `external ClassName as Alias`. O alias é o mecanismo obrigatório de interoperabilidade nesse cenário.
- Q: Como o compilador reage quando `ClassName()` é usado em uma classe com construtor não-público? → A: O Parser constrói a árvore normalmente; o Checker é responsável pela validação de acessibilidade e emite erro de compilação quando o construtor não é público.
- Q: O que acontece quando um método externo retorna `mixed` ou não tem tipo de retorno declarado? → A: Warning — o compilador avisa que chamadas subsequentes sobre a variável não serão validadas, mas a compilação prossegue normalmente.
