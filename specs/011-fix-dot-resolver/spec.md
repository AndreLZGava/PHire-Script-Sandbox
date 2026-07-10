# Feature Specification: User-Defined Method Calls as Expression Operands (BB-3 completion)

**Feature Branch**: `011-fix-dot-resolver`

**Created**: 2026-07-09

**Status**: Draft

---

## Contexto e Diagnóstico

Esta spec trata da completude do BB-3. O fix original (feature 007) resolveu chains em `AssignmentContext` e `ReturnContext` para **TypeMethods do runtime** (ex: `name.toUpperCase().trim()`). O que ainda não funciona é usar **métodos definidos pelo próprio usuário** como operandos em expressões:

```
# total(): Float {
    result = this.getBase() * this.getRate()   ← FALHA
    return result
}
```

**Causa raiz**: O `FunctionCallResolver.isTheCase()` só encontra métodos que estão no `SymbolTableManager` (registry de TypeMethods do runtime — `StringMethods`, `IntMethods`, etc.). Métodos declarados pelo usuário na própria classe (`# getBase(): Int`) **não estão nesse registry**. Quando o parser encontra `getBase` após `this.`, o `FunctionCallResolver` retorna `false` e o `FunctionCallNotFoundResolver` lança exceção.

**O que já funciona:**
- `result = name.toUpperCase()` — TypeMethod em assignment ✅
- `return this.label.toUpperCase()` — TypeMethod em return ✅
- `this.getBase()` como statement isolado (em ProgramContext) ✅
- `this.property` (acesso a propriedade) ✅

**O que não funciona:**
- `result = this.getBase() * 10` — método de usuário como operando ✅❌
- `return this.getBase() + this.getRate()` — dois métodos de usuário ❌
- `return (this.getBase() + 10) * this.getRate()` — agrupado ❌

---

## User Scenarios & Testing

### User Story 1 — Método de usuário como operando em assignment (Priority: P1)

O compilador aceita `result = this.getBase() * 10` dentro de um método de classe, onde `getBase()` é um método declarado na mesma classe.

**Por que P1**: É o caso mais simples e o prerequisite de todos os outros. Sem isso, qualquer cálculo que envolva chamada de método próprio é impossível.

**Independent Test**: Compilar uma classe com dois métodos — `getBase(): Int` e `total(): Float` onde `total` faz `result = this.getBase() * 1.1`. O PHP gerado deve conter `$result = $this->getBase() * 1.1`.

**Acceptance Scenarios**:

1. **Given** uma classe com `# getBase(): Int` declarado, **When** outro método faz `result = this.getBase() * 10`, **Then** o PHP gerado contém `$result = $this->getBase() * 10`
2. **Given** o mesmo arquivo, **When** compilado, **Then** nenhum `CompileException` é lançado
3. **Given** um método com retorno `Float`, **When** usado como operando, **Then** o tipo é preservado na expressão

---

### User Story 2 — Dois métodos de usuário em expressão binária (Priority: P1)

`return this.getBase() * this.getRate()` compila corretamente.

**Por que P1**: Caso mais comum em domínios reais (multiplicar dois getters).

**Independent Test**: Método `total()` retorna `this.getBase() * this.getRate()`. PHP gerado: `return $this->getBase() * $this->getRate();`

**Acceptance Scenarios**:

1. **Given** dois métodos declarados na classe, **When** usados juntos numa expressão binária, **Then** ambos emitem corretamente como `$this->method()`
2. **Given** tipos diferentes (`Int` e `Float`), **When** multiplicados, **Then** o compilador não lança erro de tipo

---

### User Story 3 — Método de usuário em expressão agrupada (Priority: P2)

`return (this.getBase() + 10) * this.getRate()` compila corretamente.

**Por que P2**: Depende de US1 e US2; é validação de composição com parênteses.

**Independent Test**: Mesmo caso com parênteses; PHP gerado preserva agrupamento.

**Acceptance Scenarios**:

1. **Given** uma expressão com parênteses contendo chamada de método, **When** compilada, **Then** os parênteses são preservados no PHP gerado

---

### Edge Cases

- O que acontece quando o método chamado não existe na classe? → deve lançar erro claro (como hoje, via `FunctionCallNotFoundResolver`)
- O que acontece quando `this.method()` é o único operando (sem operador binário)? → já funciona hoje, não deve regredir
- O que acontece quando o método chamado não existe na classe nem em nenhuma classe pai? → deve lançar erro claro (comportamento atual do `FunctionCallNotFoundResolver`)
- O que acontece com métodos herdados de classe pai ou avó? → incluído no escopo (herança transitiva via `extends`)
- O que acontece com métodos estáticos chamados via `this`? → fora de escopo, estão em P1-4

---

## Requirements

### Functional Requirements

- **FR-001**: O compilador DEVE reconhecer chamadas a métodos declarados pelo usuário na mesma classe como `FunctionCallResolver` válido quando o `variableOnFocus` é `ThisExpressionNode`
- **FR-002**: O retorno de um método de usuário DEVE ser propagado como foco para a próxima chamada em cadeia (para que `this.getStr().toUpperCase()` continue funcionando)
- **FR-003**: A resolução de métodos de usuário DEVE usar o tipo de retorno declarado no `MethodDeclarationNode` para atualizar o `SymbolTable` focus após a chamada
- **FR-004**: Um pré-passe DEVE rodar antes do Passe 1 (full parse) e registrar cada `ClassNode` com seus métodos declarados (nome + tipo de retorno) e seu `extends` no `SymbolTable` global, para que a cadeia de herança seja navegável durante o parse
- **FR-005**: O `ParseContext` DEVE expor o `ClassNode` da classe atualmente sendo parseada (`currentClassNode`), populado pelo `ClassBodyResolver` ao entrar no body e limpo ao sair
- **FR-006**: O `FunctionCallResolver` DEVE, quando o foco é `ThisExpressionNode`, consultar o `currentClassNode` e sua cadeia de herança (transitiva) para verificar se o método existe antes de delegar ao `FunctionCallNotFoundResolver`
- **FR-007**: Nenhuma regressão nos casos 1–69 existentes
- **FR-008**: A regra de token advance DEVE ser preservada — nenhum Resolver ou Context pode chamar `$tokenManager->advance()`

### Key Entities

- **`FunctionCallResolver`**: precisa de um caminho extra para resolver métodos cujo foco é `ThisExpressionNode`
- **`SymbolTable` / `SymbolTableManager`**: precisa expor os métodos de usuário da classe atual ao parser
- **`MethodDeclarationNode`**: já existe no AST com nome e tipo de retorno — é a fonte de verdade
- **`ClassBodyContext` / `MethodScopeContext`**: contexto onde os métodos são declarados; precisa registrar os métodos em algum lugar acessível ao parser

---

## Success Criteria

### Measurable Outcomes

- **SC-001**: `case_64` (T019/T020 da spec 006) passa com `php bin/stretch --mode=success`
- **SC-002**: Todos os casos 1–69 continuam passando sem regressão
- **SC-003**: O erro `Method "X" does not exist nor is supported` não é mais lançado para métodos declarados na mesma classe
- **SC-004**: Pelo menos 3 variações de expressão com método de usuário são validadas por sandbox cases

---

## Assumptions

- O fix se aplica apenas a métodos da **própria classe** (`this.method()`); chamadas de método em variáveis de outros tipos de usuário (ex: `builder.withName()` onde `builder` é de tipo externo) estão fora de escopo
- Os `MethodDeclarationNode`s da classe atual já estão disponíveis no AST antes da fase de parse dos corpos de método (pois o compilador faz dois passes — dependência graph + full parse)
- **Q2 — Timing investigado**: O parse completo (Passe 1) ocorre **antes** do Bind (Passe 1b). Quando o body de `total()` está sendo parseado, nenhum binder rodou — os métodos da classe não estão em nenhum registry acessível ao parser. O `ParseContext` não tem campo `currentClassNode` nem mecanismo similar. A solução requer que o `ClassBodyResolver` popule um campo `currentClassNode` no `ParseContext` ao entrar no body da classe, e limpe ao sair. O `FunctionCallResolver` consulta esse campo quando o foco é `ThisExpressionNode`.
- **Q1 — Herança transitiva incluída (via pré-passe)**: antes do Passe 1 (full parse), um pré-passe adicional registra todos os `ClassNode`s (nome + lista de métodos + `extends`) no `SymbolTable`. Durante o parse de `total()`, o `FunctionCallResolver` percorre `currentClassNode` → pai → avô consultando esse registry até encontrar o método ou esgotar a hierarquia. Abordagem escolhida: **Opção A — pré-passe de registro antes do full parse**.
- Métodos estáticos (`static function`) estão fora de escopo desta feature
