# Implementation Plan: User-Defined Method Calls as Expression Operands (BB-3 completion)

**Branch**: `011-fix-dot-resolver` | **Date**: 2026-07-09 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `specs/011-fix-dot-resolver/spec.md`

---

## Summary

O `FunctionCallResolver` só resolve métodos que estão no `SymbolTableManager` (TypeMethods do runtime). Quando o foco é `ThisExpressionNode` e o método chamado é um método de usuário (`# getBase(): Int`), o resolver retorna `false` e o `FunctionCallNotFoundResolver` lança exceção.

**Abordagem**: três mudanças coordenadas:
1. **Pré-passe de registro de ClassNodes** — antes do full parse, registrar todos os `ClassNode`s superficiais (nome + métodos + `extends`) no `SymbolTable` global para que a cadeia de herança seja navegável durante o parse
2. **`ParseContext.currentClassNode`** — o `ClassBodyResolver` popula esse campo ao entrar no body da classe e limpa ao sair; os resolvers dentro do body consultam esse campo
3. **`FunctionCallResolver` — caminho extra para `this.method()`** — quando o foco é `ThisExpressionNode`, consultar `currentClassNode` e sua cadeia de herança (transitiva) para verificar se o método existe e qual seu tipo de retorno declarado

---

## Technical Context

**Language/Version**: PHP 8.3 (compilador); PHireScript `.ps` (linguagem de entrada)

**Primary Dependencies**: Compiler internals — `FunctionCallResolver`, `ParseContext`, `ClassBodyResolver`, `Compiler.php` (orchestration), `SymbolTable`

**Storage**: N/A

**Testing**: `php bin/stretch --mode=success` (sandbox orchestrator); PHPUnit via stretch

**Target Platform**: PHireScript compiler (`phirescript/`) — Linux/PHP 8.3

**Project Type**: Compiler internal — Parser + Emitter layer

**Performance Goals**: Sem impacto mensurável — o pré-passe é O(n) sobre os ClassNodes do arquivo e roda uma vez por build

**Constraints**: **Token advance rule** — somente `Parser.php` pode chamar `$tokenManager->advance()`. Esta feature está inteiramente no Resolver/Context layer — regra não violada.

**Scale/Scope**: 5 arquivos alterados no compiler + 2–3 sandbox cases novos

---

## Constitution Check

Regra crítica do CLAUDE.md do PHireScript:

> **Token advance:** somente `Parser.php` pode chamar `$tokenManager->advance()`. Resolvers, Contexts, Binders e Checkers só usam métodos read-only (`peek()`, `lookAhead()`, etc.).

**Status**: ✅ Não violado. Todas as mudanças desta feature estão em:
- Resolvers (read-only por contrato)
- ParseContext (campo de dado, não lógica de cursor)
- Compiler.php pré-passe (roda antes do parse, não durante)

**Trinity completeness** (Scanner + Parser/Resolver + Emitter): Esta feature não adiciona nova sintaxe — `this.method()` já é tokenizado corretamente. Apenas a resolução do método está errada. Emitter não precisa de mudança — `FunctionNode` já emite `$this->method()` corretamente quando criado pelo `FunctionCallResolver`. ✅

---

## Diagnosis (fase 0 completa)

### Fluxo atual — por que `this.getBase()` falha em ExpressionContext

```
token: 'this'
  → ThisResolver.isTheCase() ✅
  → resolve(): new ThisExpressionNode; setVirtualVariable(node)

token: '.'
  → DotResolver (Statements).isTheCase() ✅
  → resolve(): focus é ThisExpressionNode; setVirtualVariable(focus) — mantém

token: 'getBase'  (próximo token: '(')
  → FunctionCallResolver.isTheCase():
      focus = ThisExpressionNode
      getFocusRawType(focus) → null  (ThisExpressionNode não tem getRawType())
      symbolTable.from(null).getFunction('getBase') → null
      symbolTable.getFunctionFromLastExecution('getBase') → null
      → retorna false ❌
  → FunctionCallNotFoundResolver.isTheCase() ✅ → lança CompileException
```

### Por que `this.getBase()` como statement isolado (ProgramContext) funciona

Em `ProgramContext`, `this.getBase()` funciona via um caminho diferente — `ThisPropertyAccessResolver` ou outro resolver que trata `this.método()` como acesso direto a property/method, sem depender do `SymbolTableManager`. Confirmado por testes existentes.

### Três passes do compilador

```
Passe 0 (pre-build):  TranspilerDependencyTree — light parse (pkg/use/external only)
                       → builds DependencyGraph
                       → ClassNode bodies NÃO são parseados aqui

Passe 1 (full parse): Transpiler::parseOnly() — bodies parseados token a token
                       → FunctionCallResolver roda aqui
                       → SymbolTable ainda NÃO tem ClassNodes registrados

Passe 1b (bind):      Transpiler::bindProgram() — TypeRegistrationBinder registra ClassNodes
                       → Acontece APÓS o parse

Passe 2 (check+emit): Checker + Emitter
```

**Problema**: métodos de usuário precisam estar disponíveis no Passe 1, mas só são registrados no Passe 1b.

### Solução: pré-passe leve entre Passe 0 e Passe 1

Após o `DependencyGraphBuilder` estar pronto e antes de `Transpiler::parseOnly()`, iterar sobre os `preBuildPrograms` (ASTs superficiais do Passe 0) e registrar no `SymbolTable` o mapa `className → {methods: [{name, returnType}], extends: string|null}`.

Os `preBuildPrograms` **já existem em memória** neste ponto (linha 93 do `Compiler.php`). O pré-passe não precisa reler nenhum arquivo — só iterar sobre o que foi parsado no Passe 0.

**Porém**: no Passe 0 (`TranspilerDependencyTree`), o body das classes não é parsado (só `pkg`/`use`/`external`). Os `MethodDeclarationNode`s não existem nos `preBuildPrograms`. Portanto, o pré-passe precisa de uma varredura adicional dos tokens para extrair métodos.

**Abordagem alternativa mais simples**: fazer o pré-passe de registro **dentro do Passe 1 (full parse)**, no momento em que o `ClassBodyResolver` é chamado. Nesse momento, o `ClassNode` está sendo construído e seus `MethodDeclarationNode`s serão adicionados progressivamente ao `ClassBodyNode`. A dificuldade é que quando `total()` está sendo parseado, `getBase()` pode ainda não ter sido encontrado pelo parser (se `total` vier antes de `getBase` no arquivo).

**Abordagem definitiva — two-step no Passe 1**:

Opção A (mais robusta): adicionar um **mini-passe 0.5** que faz uma varredura rápida do arquivo apenas para extrair `class Name extends Parent { method(): ReturnType }` sem parsear bodies. Isso popula o `SymbolTable` antes do full parse.

Opção B (mais simples, limitada): durante o `ClassBodyContext`, ao fechar cada `MethodDeclarationNode`, registrá-lo imediatamente no `ParseContext` — mas isso só funciona se o método chamador vier depois do método chamado no arquivo.

**Decisão**: Opção A — mini-passe 0.5 com varredura por regex/tokens dos arquivos. Dado que o `ClassBodyResolver` recebe o `context->node` (o `ClassNode`), e o `ClassNode` tem `token` com a posição do arquivo, podemos usar `lookAhead` para extrair a assinatura dos métodos antes de entrar no body.

**Refinamento**: usar o `DependencyGraphBuilder::preBuildPrograms` que já existe — mas como o Passe 0 não parseia bodies, precisamos de uma segunda varredura leve. A forma mais limpa é: no `ClassBodyResolver.resolve()`, antes de entrar no `ClassBodyContext`, fazer um `lookAhead` dos tokens restantes para extrair todos os `# methodName(): ReturnType` da classe e registrá-los no `ParseContext` como `currentClassMethods`.

---

## Project Structure

### Documentation (this feature)

```text
specs/011-fix-dot-resolver/
├── plan.md              ← este arquivo
├── spec.md              ← especificação
├── research.md          ← achados da investigação
├── data-model.md        ← modelo de dados
├── vscode-extension.md  ← documentação para a extensão VS Code
└── checklists/
    └── requirements.md
```

### Source Code (arquivos a modificar/criar)

```text
phirescript/src/
├── Compiler/
│   └── Parser/
│       ├── ParseContext.php                              ← ADD: currentClassMethods, currentClassName
│       └── Ast/
│           ├── Resolver/
│           │   ├── Root/Class/
│           │   │   └── ClassBodyResolver.php             ← MODIFY: registra métodos via lookAhead
│           │   └── Expressions/
│           │       └── FunctionCallResolver.php          ← MODIFY: caminho para ThisExpressionNode
│           └── Nodes/
│               └── Expressions/
│                   └── ThisExpressionNode.php            ← ADD: getRawType() → 'This' sentinel

samples/success/
└── case_64/          ← NEW: T019/T020 da spec 006 — method calls as operands
    ├── Calculator.ps
    ├── Calculator.psc
    ├── CalculatorTest.php
    └── CaseValidation.php
```

---

## Phase 0: Research — Achados

### Decisão 1: Como registrar métodos da classe antes do parse do body

**Chosen**: `ClassBodyResolver.resolve()` faz `lookAhead` dos tokens à frente para extrair assinaturas de métodos (`# name(): ReturnType`) da classe atual antes de entrar no `ClassBodyContext`. Registra no `ParseContext` como `currentClassMethods: array<string, string>` (nome → tipo de retorno raw).

**Rationale**: 
- Não viola a token advance rule (lookAhead é read-only)
- Não requer novo passe de compilação
- Funciona mesmo quando o método chamador vem antes do método chamado no arquivo
- `ClassBodyResolver` já tem acesso a `$context->node` (o `ClassNode` com nome e extends)

**Alternatives considered**:
- Mini-passe 0.5 separado: mais limpo arquiteturalmente mas requer novo artefato de orquestração
- Registro progressivo (só métodos já vistos): falha quando `total()` vem antes de `getBase()`

**Limitação**: `lookAhead` precisa ser suficientemente robusto para encontrar todos os métodos sem confundir com corpos. Pattern: token `#` seguido de identifier seguido de `(` → é uma declaração de método.

### Decisão 2: Como representar o tipo de retorno de métodos de usuário no FunctionCallResolver

**Chosen**: `FunctionCallResolver` quando o foco é `ThisExpressionNode`:
1. Verifica se `$token->value` existe em `$parseContext->currentClassMethods`
2. Se sim: cria `FunctionNode` com `variableBase = ThisExpressionNode`; emitter já sabe gerar `$this->method()` para FunctionNode com ThisExpressionNode como base
3. Atualiza `variableOnFocus` com um `LiteralNode` do tipo de retorno (usando `getNewVirtualVariable` já existente)

**Rationale**: Reutiliza toda a lógica de emissão existente do `FunctionCallResolver` — apenas o caminho de `isTheCase()` e o lookup de `functionDefinition` mudam.

**Alternatives considered**:
- Criar `UserMethodBaseMethods` que wrapper `MethodDeclarationNode` como `BaseMethods` — mais limpo mas requer criação de classe nova e wiring no SymbolTableManager

### Decisão 3: Herança transitiva — como navegar a cadeia

**Chosen**: `ParseContext` expõe `currentClassHierarchy: array<string, array>` — mapa de className → métodos + extends, populado durante o lookAhead de cada arquivo. Quando `FunctionCallResolver` não encontra o método na classe atual, sobe pelo `extends` até encontrar ou esgotar.

**Rationale**: O `ClassBodyResolver` processa cada arquivo na ordem do Passe 1. Se classe B estende A e A está no mesmo arquivo e vem antes, o mapa já terá A quando B for processada. Se A está em arquivo diferente, o pré-passe precisa processar todos os arquivos antes — isso está garantido porque `Compiler.php` itera todos os `sourceFiles` antes de chamar `loadAndCompile`.

**Limitação**: Se A está em arquivo não ainda parseado no Passe 1, o mapa pode estar incompleto. Mitigation: o `ClassBodyResolver` é chamado no início da classe (ao abrir `{`), então o lookAhead registra todos os métodos da classe antes de qualquer body ser processado. Para herança cross-file, o `ClassBodyResolver` de A roda antes do de B se A vem antes na ordem topológica — que é garantida pelo `DependencyGraphBuilder`.

---

## Phase 1: Design & Contracts

### Data Model

#### `ParseContext` — campos novos

```php
// Métodos da classe atualmente sendo parseada: nome → tipo de retorno raw (ex: 'Int', 'String', 'Void')
public array $currentClassMethods = [];

// Nome da classe atualmente sendo parseada (null fora de uma classe)
public ?string $currentClassName = null;

// Mapa global de todos os ClassNodes já vistos neste arquivo: className → {methods, extends}
// Usado para herança transitiva
public array $classMethodRegistry = [];
```

#### `ClassBodyResolver` — lógica de lookAhead

```php
public function resolve(Token $token, ParseContext $parseContext, AbstractContext $context): void
{
    // 1. Extrair nome da classe do contexto pai
    $className = $context->node->name;
    $extendsName = $context->node->extends?->name ?? null;

    // 2. lookAhead dos tokens à frente para extrair declarações de método
    $methods = $this->extractMethodSignatures($parseContext->tokenManager);

    // 3. Registrar no ParseContext
    $parseContext->currentClassName = $className;
    $parseContext->currentClassMethods = $methods;
    $parseContext->classMethodRegistry[$className] = [
        'methods' => $methods,
        'extends' => $extendsName,
    ];

    // 4. Entrar no ClassBodyContext (comportamento existente)
    $node = new ClassBodyNode(...);
    $parseContext->contextManager->enter(new ClassBodyContext($node));
    $context->addChild($node);
}

private function extractMethodSignatures(TokenManager $tm): array
{
    // Usa lookAhead para encontrar padrão: T_HASH identifier '(' ... ')' ':' identifier
    // sem avançar o cursor
}
```

#### `FunctionCallResolver` — caminho extra para ThisExpressionNode

```php
public function isTheCase(Token $token, ParseContext $parseContext, AbstractContext $context): bool
{
    $focus = $parseContext->variables->getVariableOnFocus();

    // NOVO: caminho para this.userMethod()
    if ($focus instanceof ThisExpressionNode && $token->isIdentifier()
        && $parseContext->tokenManager->getNextTokenAfterCurrent()->isOpeningParenthesis()
    ) {
        return $this->resolveFromClassHierarchy($token->value, $parseContext) !== null;
    }

    // caminho existente (TypeMethods do runtime)
    return $token->isIdentifier()
        && $parseContext->tokenManager->getNextTokenAfterCurrent()->isOpeningParenthesis()
        && (
            $parseContext->symbolTable->getFunctionFromLastExecution($token->value)
            || $parseContext->symbolTable->from($this->getFocusRawType($focus))->getFunction($token->value)
        );
}

private function resolveFromClassHierarchy(string $methodName, ParseContext $parseContext): ?string
{
    $className = $parseContext->currentClassName;
    while ($className !== null) {
        $entry = $parseContext->classMethodRegistry[$className] ?? null;
        if ($entry === null) break;
        if (isset($entry['methods'][$methodName])) {
            return $entry['methods'][$methodName]; // tipo de retorno
        }
        $className = $entry['extends'];
    }
    return null;
}
```

#### `ThisExpressionNode` — sentinel type

Para que o tipo de retorno de `this.getBase()` possa alimentar chains subsequentes (ex: `this.getStr().toUpperCase()`), o `FunctionCallResolver` cria um `LiteralNode` com o tipo de retorno declarado e o seta como `variableOnFocus` — usando o `getNewVirtualVariable()` já existente.

### VS Code Extension

Documentado em `vscode-extension.md`.

### Sandbox Cases

**case_64** — método de usuário como operando (T019/T020 da spec 006):

```
pkg PHireScript.Samples64

class Calculator as scoped {
    Int base
    Float rate

    # getBase(): Int {
        return this.base
    }

    # getRate(): Float {
        return this.rate
    }

    # total(): Float {
        result = this.getBase() * this.getRate()
        return result
    }

    # withBonus(): Float {
        return (this.getBase() + 10) * this.getRate()
    }
}
```

`CaseValidation` asserta:
- `$result = $this->getBase() * $this->getRate()`
- `return ($this->getBase() + 10) * $this->getRate()`

**case_N** (herança) — para validar herança transitiva:

```
pkg PHireScript.SamplesN

class Base as scoped {
    Int value

    # getValue(): Int {
        return this.value
    }
}

class Child extends Base {
    # doubled(): Int {
        return this.getValue() * 2
    }
}
```

---

## Sequência de implementação

```
1. ParseContext.php       — adicionar currentClassMethods, currentClassName, classMethodRegistry
2. ClassBodyResolver.php  — lookAhead + registro; limpar ao fechar ClassBodyContext
3. ClassBodyContext.php   — afterClose() ou canClose() limpa parseContext.currentClassName
4. FunctionCallResolver.php — caminho extra para ThisExpressionNode + resolveFromClassHierarchy()
5. Sandbox case_64        — Calculator.ps + CaseValidation + CalculatorTest
6. Sandbox case herança   — Base + Child + CaseValidation
7. php bin/stretch        — validar sem regressões
```

---

## Riscos e Mitigações

| Risco | Mitigação |
|-------|-----------|
| lookAhead passa dos limites do arquivo | Guard com `isEndOfTokens()` no loop |
| Herança cross-file com arquivo pai não ainda registrado | A ordem topológica do DependencyGraphBuilder garante que o pai é parseado antes do filho |
| `ClassBodyContext` aninhado (classe dentro de classe) | PHireScript não suporta classes aninhadas — não é um caso real |
| Regressão em cases existentes (55–69) | Suite completa roda como gate final antes de commit |
