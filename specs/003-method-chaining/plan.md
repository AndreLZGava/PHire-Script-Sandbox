# Implementation Plan: Method Chaining

**Branch**: `003-method-chaining` | **Date**: 2026-06-03 | **Spec**: [spec.md](spec.md)

---

## Summary

Implementar method chaining funcional no PHireScript — encadeamento de chamadas de método sobre variáveis e literais com verificação de tipo em cada elo, emissão inline nested no PHP gerado, safe navigation operator `?.`, e 5 regras de validação semântica no Checker. A base de infraestrutura (TypeMethods, FunctionNode, FunctionCallContext, SymbolTableManager) já existe; o bloqueador raiz é que `VariableReferenceResolver` rejeita variáveis seguidas de `.` e `DotResolver` (Statements) não sinaliza o foco — dois fixes cirúrgicos que desbloqueiam todos os cenários de chain.

---

## Technical Context

**Language/Version**: PHP 8.2 (compilador), PHireScript `.ps` (linguagem fonte)

**Primary Dependencies**: nikic/php-parser (pós-emissão), PHPUnit (testes unitários do compilador), PHPStan nível 9, Rector

**Storage**: N/A — compilador stateless por arquivo

**Testing**: `vendor/bin/phpunit` dentro de `phirescript/`; orchestrator sandbox `php bin/stretch --mode=success --tags=method-chaining` e `--mode=error --tags=method-chaining`

**Target Platform**: CLI PHP 8.2+

**Project Type**: Compilador/transpiler

**Performance Goals**: Compilação de um arquivo `.ps` típico (50–200 linhas) em < 100ms; nenhuma variável temporária gerada para chains em assignment context

**Constraints**: Token advance apenas em `Parser.php:64` — Resolvers, Contexts, Binders, Checkers só usam métodos read-only do TokenManager. Toda nova classe Checker e Binder requer `#[CompilerPass(order: N)]` para ser descoberta por PassDiscovery.

---

## Constitution Check

A constituição do projeto ainda é um template sem conteúdo específico. Usando os princípios derivados do `phirescript/CLAUDE.md` e da arquitetura documentada:

| Gate | Status | Observação |
|---|---|---|
| Token advance apenas em `Parser.php:64` | ✓ Nenhuma violação planejada | DotResolver usa `setVirtualVariable` (read-only no foco) |
| Trinity completa (Node + Context + Resolver) | ✓ Verificado por tipo | SafeNavigationResolver não precisa de Context próprio — usa contexto pai |
| `#[CompilerPass]` em Binders/Checkers | ✓ A aplicar | `ChainConsistencyChecker` precisará do atributo |
| Emitter registrado em `src/Emitter.php` | ✓ Sem novos emitters standalone | `FunctionEmitter` existente é atualizado |
| Sem variáveis temporárias em assignment context | ✓ Estratégia inline nested confirmada | |

Nenhuma violação. Prosseguir para implementação.

---

## Project Structure

### Documentation (this feature)

```text
specs/003-method-chaining/
├── plan.md              # Este arquivo
├── research.md          # Análise dos arquivos-chave (abaixo)
├── data-model.md        # Mudanças no AST
├── contracts/           # Regras do Checker como contratos
└── tasks.md             # Gerado por /speckit-tasks
```

### Source Code — arquivos afetados

```text
phirescript/src/
├── Compiler/
│   ├── Scanner.php                                        # [MODIFICAR] adicionar token ?.
│   ├── Checker.php                                        # [MODIFICAR] registrar ChainConsistencyChecker
│   │
│   ├── Parser/Ast/
│   │   ├── Resolver/
│   │   │   ├── Expressions/Types/
│   │   │   │   └── VariableReferenceResolver.php          # [FIX] remover exclusão !==  '.'
│   │   │   ├── Expressions/
│   │   │   │   └── FunctionCallResolver.php               # [FIX] setVirtualVariable + type tracking
│   │   │   └── Statements/
│   │   │       ├── DotResolver.php                        # [FIX] implementar resolve()
│   │   │       └── SafeNavigationResolver.php             # [NOVO] handle ?.
│   │   │
│   │   ├── Nodes/Declarations/
│   │   │   └── FunctionNode.php                           # [MODIFICAR] + $safeNavigation flag
│   │   │
│   │   └── Context/Expressions/
│   │       └── FunctionCallContext.php                    # [MODIFICAR] canClose += isSafeNavigation
│   │
│   ├── Checker/Expression/
│   │   └── ChainConsistencyChecker.php                    # [NOVO] 5 regras de validação
│   │
│   └── Emitter/Declarations/
│       └── FunctionEmitter.php                            # [MODIFICAR] inline nested + null guard

samples/
├── success/
│   ├── case_42/    # Chain básica String — replace + length
│   ├── case_43/    # Auto-atribuição explícita
│   ├── case_44/    # Chain em contexto if
│   ├── case_45/    # Chain sobre literal
│   ├── case_46/    # Safe navigation ?.
│   ├── case_47/    # Chain multi-linha
│   └── case_48/    # Chain cruzando tipos (String → Array → Int)
└── error/
    └── case_49/    # Dead chain + void termination + nullable sem ?. + Mixed

prompts/
└── method-chaining-out-of-scope.md                        # [NOVO] itens fora do escopo
```

---

## Research

### Diagnóstico do bloqueador raiz

**Causa 1 — VariableReferenceResolver**

```php
// phirescript/src/Compiler/Parser/Ast/Resolver/Expressions/Types/VariableReferenceResolver.php:18
public function isTheCase(...): bool
{
    return $token->isIdentifier() &&
        $parseContext->variables->getVariable($token->value) &&
        $parseContext->tokenManager->getNextTokenAfterCurrent()->value !== '.'; // ← BLOQUEADOR
}
```

A condição `!== '.'` faz o resolver rejeitar `mystring` quando o próximo token é `.`. Como `getVariable` nunca é chamado, `variableOnFocus` nunca é setado. Todos os resolvers subsequentes (inclusive `FunctionCallResolver`) operam com foco null.

**Causa 2 — DotResolver (Statements)**

```php
// phirescript/src/Compiler/Parser/Ast/Resolver/Statements/DotResolver.php:21
public function resolve(...): void
{
    // vazio — não faz nada
}
```

Mesmo que o foco fosse setado antes, o `.` não sinaliza ao contexto que o próximo token é um método da chain.

**Mecanismo de chain já existente mas inativo**

`FunctionCallResolver.resolve()` tem esta linha comentada:
```php
//$parseContext->variables->setVirtualVariable($function);
```

Quando ativa, faz o `FunctionNode` recém-criado (`replace(...)`) tornar-se o novo foco, de modo que o próximo `FunctionCallResolver` (para `length`) veja o FunctionNode como `variableBase`. Isso é exatamente o que habilita a emissão inline nested:

```
length.variableBase = FunctionNode(replace)
                    → strlen( emit(FunctionNode(replace)) )
                    → strlen( str_replace('is','really', $mystring) )
```

**Mecanismo de tipo já existente mas incompleto**

`FunctionCallResolver.overrideVariableOnFocus()` atualiza o tipo do `variableBase` para o tipo de retorno do método:
```php
$function->variableBase->type = $newVariable; // ex: StringNode para retorno 'String'
```

Mas `getNewVirtualVariable()` só suporta `Array`, `String`, `Int`, `Float`, `Object`, `Bool`. SuperTypes e Collection types lançam exceção.

**FunctionCallContext.canClose() e chains**

```php
public function canClose(Token $token, ParseContext $parseContext): bool
{
    return $token->isDot() || $token->isEndOfLine();
}
```

Já fecha no `.` — o mecanismo de transição entre elos já existe. Precisa adicionar `isSafeNavigation()` para o operador `?.`.

**`getFunctionFromLastExecution` como mecanismo de lookup em chain**

`FunctionCallResolver.isTheCase()` usa OR:
```php
$parseContext->symbolTable->getFunctionFromLastExecution($token->value) ||
$parseContext->symbolTable->from($focusType)->getFunction($token->value)
```

O `getFunctionFromLastExecution` já é um mecanismo de lookup baseado no tipo de retorno da última chamada. Precisa ser alimentado corretamente quando a chain avança.

---

### Estratégia de emissão — decisão final

**Chains com atribuição**: inline nested, sem variáveis temporárias.
```
result = mystring.replace('a','b').replace('c','d').length()
→ $result = strlen(str_replace('c', 'd', str_replace('a', 'b', $mystring)));
```

O mecanismo: `length.variableBase = FunctionNode(replace_2)`, e `replace_2.variableBase = FunctionNode(replace_1)`. O FunctionEmitter resolve recursivamente via `emit(node->variableBase)` ao substituir `@self`.

**Safe navigation `?.`**: variável temporária `$__chain_N` + ternário.
```
result = mystring.between('a','b')?.length()
→ $__chain_0 = /* between code */;
  $result = $__chain_0 !== null ? strlen($__chain_0) : null;
```

**Chains sem atribuição em contexto de expressão**: mantém inline, o resultado alimenta o contexto pai.
```
if(mystring.length() > 5)
→ if(strlen($mystring) > 5)
```

---

### Token `?.` — decisão de Scanner

`?.` deve ser um token único, tokenizado pelo Scanner com padrão `\?\.` (regex), com prioridade superior ao token `.` isolado. Não conflita com `method?` porque `?` em nome de método é parte do identificador (tokenizado junto). O `?.` aparece apenas após `)`.

`Token.php` recebe método `isSafeNavigation(): bool { return $this->value === '?.'; }`.

---

## Data Model

Ver `data-model.md` ao lado.

---

## Contracts

Ver `contracts/chain-checker-rules.md` ao lado.

---

## Implementation Order

A ordem segue o pipeline do compilador (Scanner → Parser → Checker → Emitter) e respeita as dependências entre fixes.

```
Fase 1 — Bloqueador raiz (Parser)
  Fix VariableReferenceResolver
  Fix DotResolver (Statements)
  Fix FunctionCallResolver (setVirtualVariable + type tracking)
  Fix getNewVirtualVariable (estender para SuperTypes)
  Fix FunctionCallContext (canClose += safeNavigation)

Fase 2 — Scanner + Safe Navigation
  Adicionar token ?. no Scanner
  Adicionar Token.isSafeNavigation()
  Adicionar SafeNavigationResolver
  Adicionar $safeNavigation flag em FunctionNode
  Adicionar multi-line chain handling (EOL antes de .)

Fase 3 — Checker
  Criar ChainConsistencyChecker com 5 regras
  Registrar em Checker.php

Fase 4 — Emitter
  Atualizar FunctionEmitter: inline nested
  Atualizar FunctionEmitter: null guard para safeNavigation

Fase 5 — Sandbox + Documentação
  Criar cases 42–49
  Criar prompts/method-chaining-out-of-scope.md
  Atualizar phirescript/CLAUDE.md (Language Feature Status)
  Atualizar phirescript/architecture.md
```
