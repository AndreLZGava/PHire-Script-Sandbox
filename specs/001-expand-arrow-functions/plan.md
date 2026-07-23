# Implementation Plan: Expansão de Arrow Functions

**Branch**: `001-expand-arrow-functions` | **Date**: 2026-05-25 | **Spec**: [spec.md](spec.md)

---

## Summary

Expandir o suporte a arrow functions no compilador PHireScript para cobrir: zero parâmetros, múltiplos parâmetros tipados (incluindo union types), valores default, captura automática de variáveis do escopo externo (geração de `use (...)` no PHP), e validação semântica no checker. A causa raiz do bug de múltiplos parâmetros foi identificada em `OpeningParamsDeclarationResolver` — será corrigida com um resolver dedicado.

---

## Technical Context

**Language/Version**: PHP 8.2+

**Primary Dependencies**: PHireScript compiler (pipeline interna), PHPUnit ^12.5, PHPStan ^2.1, PHP-CS-Fixer ^3.92

**Storage**: N/A — compilador, I/O via sistema de arquivos

**Testing**: PHPUnit (unit tests em `phirescript/tests/`) + orchestrador sandbox (`php bin/stretch --mode=success`)

**Target Platform**: CLI, Linux/macOS

**Project Type**: Compilador/transpiler — feature expansion dentro do pipeline existente

**Performance Goals**: Correção primeiro; sem metas de throughput para esta feature

**Constraints**: PHPStan nível 9 sem supressões, PSR-12, um commit por feature completa com sandbox passing

---

## Constitution Check

*Constitution não configurada neste projeto — gates padrão aplicados.*

- [x] PHPStan nível 9 deve passar após cada arquivo novo/modificado
- [x] PSR-12 (php-cs-fixer) aplicado
- [x] Testes unitários passando (`vendor/bin/phpunit` em `phirescript/`)
- [x] Sandbox passando (`php bin/stretch --mode=success`) para os novos casos
- [x] Sem regressões nos 34 casos de sucesso existentes

---

## Project Structure

### Documentação (esta feature)

```text
specs/001-expand-arrow-functions/
├── plan.md          ← este arquivo
├── spec.md          ← especificação
├── research.md      ← Phase 0 (concluído)
├── data-model.md    ← Phase 1 (concluído)
└── checklists/
    └── requirements.md
```

### Arquivos do compilador a modificar

```text
phirescript/src/
├── Compiler/Parser/Ast/
│   ├── Context/Declarations/
│   │   └── ArrowFunctionDeclarationContext.php   ← trocar resolver de params
│   └── Resolver/Expressions/
│       └── ArrowFunctionOpeningParensResolver.php ← NOVO
├── Compiler/Emitter/Declarations/
│   └── ArrowFunctionEmitter.php                  ← lógica de use() + zero params
└── Compiler/Checker/Declaration/
    └── ArrowFunction/
        └── ArrowFunctionChecker.php              ← NOVO

phirescript/tests/Compiler/
└── ArrowFunctionCheckerTest.php                  ← NOVO (unit tests do checker)
```

### Arquivos do sandbox a criar

```text
samples/success/
├── case_35/          ← zero params + 1 param
├── case_36/          ← múltiplos params + union types
├── case_37/          ← valores default
└── case_38/          ← captura de variáveis externas (use automático)
```

---

## Implementation Steps

### Step 1 — Corrigir bug de múltiplos parâmetros (parser)

**Arquivo**: `phirescript/src/Compiler/Parser/Ast/Resolver/Expressions/ArrowFunctionOpeningParensResolver.php` *(novo)*

Criar resolver que dispara quando `$token->isOpeningParenthesis()`, independente do token anterior. Ao resolver, cria `ParamsListNode` e entra em `ParameterListContext`. Registrá-lo em `ArrowFunctionDeclarationContext` no slot `'parameters'`, substituindo `OpeningParamsDeclarationResolver`.

**Validação**: arrow function com 2+ parâmetros compila sem erro; `ParamArgumentNode.types` e `name` estão corretos para cada parâmetro.

---

### Step 2 — Corrigir ArrowFunctionEmitter (zero params + use automático)

**Arquivo**: `phirescript/src/Compiler/Emitter/Declarations/ArrowFunctionEmitter.php` *(modificar)*

Mudanças:
1. Emitir `function()` quando `$node->parameters` for null ou lista vazia
2. Implementar detecção de variáveis capturadas: percorrer `$node->bodyCode` recursivamente coletando `VariableReferenceNode`s; subtrair os nomes dos parâmetros em `$node->parameters->params`; gerar `use ($var1, $var2)` para as referências restantes
3. Posicionar `use (...)` entre a lista de parâmetros e o tipo de retorno: `function(params) use ($vars): returnType { body }`

**Validação**: arrow functions sem parâmetros, com parâmetros, e com/sem referências externas emitem PHP correto.

---

### Step 3 — Criar ArrowFunctionChecker

**Arquivo**: `phirescript/src/Compiler/Checker/Declaration/ArrowFunction/ArrowFunctionChecker.php` *(novo)*

Implementar `#[CompilerPass(order: N)]` (checar ordem dos passes existentes para não conflitar). Validar:
- Corpo vazio + tipo de retorno ≠ `Void` → `CheckerException`
- `return valor` + tipo de retorno = `Void` → `CheckerException`
- Tipo de retorno ≠ `Void` + nenhum `return` no corpo → `CheckerException`

**Arquivo**: `phirescript/tests/Compiler/ArrowFunctionCheckerTest.php` *(novo)*

Testes unitários cobrindo os três casos acima mais o caso feliz.

---

### Step 4 — Casos de integração no sandbox

Criar `samples/success/case_35/` a `case_38/` com `.phs` e `CaseValidation.php` para os cenários da spec. Cada `CaseValidation.php` usa `assertHasMessage` para verificar que a compilação sucede e valida o PHP gerado.

Pacotes: `pkg PHireScript.Samples35`, `pkg PHireScript.Samples36`, etc.

---

## Ordem de execução

```
Step 1 (parser fix)
  → Step 2 (emitter)
  → Step 3 (checker + unit tests)
  → Step 4 (sandbox cases)
  → composer quality (format + analyse)
  → php bin/stretch --mode=success
  → commit
```

Steps 1–2 são sequenciais (emitter depende do parser correto). Step 3 é independente. Step 4 depende de 1–3.
