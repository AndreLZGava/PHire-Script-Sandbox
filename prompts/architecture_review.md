# PHireScript — Revisão Arquitetural

> Documento atualizado após os ciclos de refatoração de 2025–2026.
> Organização: itens pendentes ordenados por facilidade × impacto; itens concluídos ao final.

---

## Índice

1. [Pendente — alto impacto, baixo esforço](#1-pendente--alto-impacto-baixo-esforço)
2. [Pendente — alto impacto, médio esforço](#2-pendente--alto-impacto-médio-esforço)
3. [Pendente — alto impacto, alto esforço](#3-pendente--alto-impacto-alto-esforço)
4. [Pendente — roadmap futuro](#4-pendente--roadmap-futuro)
5. [Novas melhorias identificadas](#5-novas-melhorias-identificadas)
6. [Concluído](#6-concluído)

---

## 1. Pendente — alto impacto, baixo esforço

### 1.1 `php -l` chamado para cada arquivo compilado

`FileCompiler::compileFile()` executa `exec("php -l $output")` para cada arquivo gerado, forkando um processo PHP por arquivo (~50ms cada).

**Fix:** Mover a validação de sintaxe para fora do loop principal. Opções:
- Validar apenas em modo `dev: true` (config já existe)
- Fazer um batch `php -l` no final do build, não por arquivo
- Substituir por validação via `nikic/php-parser` (já no pipeline) que já faz parse do PHP gerado

---

### 1.2 Exceptions genéricas no DependencyGraphBuilder

`DependencyGraphBuilder` usa `throw new \Exception(...)` em vários pontos em vez de `CompileException`. Isso quebra o tratamento granular de erros e mistura erros de compilação com erros de runtime PHP.

**Fix:** Substituir por `CompileException` ou criar `DependencyException extends CompileException`.

---

### 1.3 TokenManager — cópia de arrays via `array_slice`

`getNextAfterFirstFoundElement()` cria uma cópia de até 1000 tokens via `array_slice` só para buscar um elemento:

```php
$leftTokens = $this->getLeftTokens(1000); // copia 1000 tokens
foreach ($leftTokens as $key => $token) { ... }
```

**Fix:** Substituir por iteração direta por índice sem cópia:

```php
$end = min($this->cursor + 1000, count($this->tokens));
for ($i = $this->cursor; $i < $end; $i++) {
    if (...) return $this->tokens[$i];
}
```

---

### 1.4 CacheManager não conectado ao pipeline principal

O `CacheManager` existe e o `SymbolTableManager` já usa para cachear type methods. Mas tokens (Scanner) e ASTs (Parser) ainda são recomputados em cada build para todos os arquivos, mesmo os que não mudaram.

**Fix de curto prazo:** Conectar `CacheManager` ao `Transpiler` para cachear tokens + AST por arquivo usando o hash do arquivo como chave. Somente arquivos cujo hash mudou precisam ser re-scaneados e re-parseados. O `DependencyGraphBuilder` já tem as informações de quais arquivos mudaram.

---

## 2. Pendente — alto impacto, médio esforço

### 2.1 Dupla compilação para o grafo de dependências

`Compiler.compile()` ainda faz dois passes:
1. Parse parcial de todos os arquivos via `load()` para construir o grafo
2. Parse + bind + check + emit completo via `loadAndCompile()`

Cada arquivo `.ps` é escaneado e parseado **duas vezes** em cada build.

**Fix:** Reusar as ASTs do passo 1 no passo 2. O `Transpiler` já tem `parseOnly()` (Phase 0) — basta armazenar os resultados e passá-los para as fases seguintes ao invés de reler os arquivos do disco.

---

### 2.2 Scanner — regex sequencial sem dispatch

O Scanner testa cada posição do cursor contra ~25 patterns em ordem sequencial. Para um arquivo de 500 linhas (~2000 tokens): ~50.000 tentativas de regex por arquivo.

**Fix:** Classificar pelo primeiro caractere antes de entrar no loop de patterns:

```php
private const CHAR_DISPATCH = [
    '/'  => ['T_COMMENT', 'T_MODIFIER'],
    '"'  => ['T_STRING'],
    "'"  => ['T_STRING'],
    '.'  => ['T_RANGE', 'T_MODIFIER', 'T_SYMBOL'],
    // letras → T_KEYWORD, T_PRIMITIVE, T_SUPERTYPE, T_IDENTIFIER
    // dígitos → T_RANGE, T_NUMBER
];
```

Reduz as tentativas de O(tokens × 25) para O(tokens × 3–5) na média.

---

### 2.3 VariableManager com escopo plano

O `VariableManager` usa um array simples sem hierarquia de escopos. Variáveis de escopos internos (if, loop, closure) existem no mesmo namespace que variáveis externas.

**Vai quebrar com:** closures que capturam variáveis, loops aninhados com variáveis de iteração, block scoping.

**Fix:**

```php
class VariableManager {
    private array $scopeStack = [[]];

    public function enterScope(): void { $this->scopeStack[] = []; }
    public function exitScope(): void  { array_pop($this->scopeStack); }

    public function get(string $name): ?VariableDeclarationNode {
        for ($i = count($this->scopeStack) - 1; $i >= 0; $i--) {
            if (isset($this->scopeStack[$i][$name])) return $this->scopeStack[$i][$name];
        }
        return null;
    }
}
```

---

### 2.4 ClassScanner — `token_get_all` em cada build

`ClassScanner::listClassesExtending()` faz `token_get_all()` em cada arquivo `.php` de tipos a cada build para descobrir MetaTypes e SuperTypes. O resultado é estático entre builds.

**Fix:** Cachear via `CacheManager` (já existe). O scan só reexecuta quando um arquivo em `Runtime/` muda.

---

### 2.5 FileWatcher com polling ineficiente

O `FileWatcher` usa `while(true)` + `usleep(900000)`, recria `RecursiveDirectoryIterator`, itera todos os arquivos e calcula `md5_file()` de cada um a cada 0.9s.

**Fix de curto prazo:** Usar o hash do `CacheManager` (já calculado) em vez de recalcular `md5_file` no watcher.

**Fix de longo prazo:** Usar `inotify` (Linux) via `inotify_init()` / `inotify_add_watch()` — elimina polling completamente; o kernel notifica quando um arquivo muda.

---

## 3. Pendente — alto impacto, alto esforço

### 3.1 PhpFileGeneratorHandler — double parse

Após o Emitter gerar o código PHP como string, o `PhpFileGeneratorHandler` re-parseia essa string com `nikic/php-parser`, aplica visitors, e reformata. O pipeline é:

```
AST PHireScript → string PHP → parse nikic → AST PHP → string PHP
```

**Fix de longo prazo:** Emitir diretamente nós `PhpParser\Node` ao invés de strings. Elimina o round-trip inteiro:

```
AST PHireScript → AST PHP → string PHP
```

Cada `NodeEmitter` retornaria um `PhpParser\Node` ao invés de uma string. Alto esforço — requer reescrita de todos os ~40 emitters.

---

### 3.2 Error recovery no Parser

O parser aborta no primeiro erro com `CompileException`. Para UX de qualidade:
- Acumular erros e continuar parseando (sync on safe tokens como `\n` ou `}`)
- Reportar todos os erros de um arquivo de uma vez
- Em modo watch, mostrar erros sem matar o processo

Requer um mecanismo de "panic mode" no ContextManager para descartar tokens até um ponto de sincronização seguro.

---

### 3.3 SymbolTable raiz ainda primitiva

A `SymbolTable` raiz (usada pelo Binder/Checker) ainda registra builtins hardcoded (`toUpperCase → STRING`, `push → ARRAY`) e usa `$linePosition` como chave secundária — vinculando o tipo à posição no arquivo ao invés do escopo lógico.

A `SymbolTableManager` (Parser) já é a fonte real de verdade sobre tipos. A `SymbolTable` raiz deveria ser simplificada para ser apenas um registry de tipos declarados pelo usuário, delegando lookups de builtins para o `SymbolTableManager`.

---

## 4. Pendente — roadmap futuro

### 4.1 Generics com propagação de tipo

`List<T>`, `Map<T>`, `Queue<T>`, `Stack<T>` existem no Runtime mas sem validação de tipo em bind time e sem propagação do tipo genérico (`List<String>.map()` → qual é o tipo retornado?).

---

### 4.2 ContextManager sem limite de profundidade

O stack de contexts não tem limite. Arquivos com nesting muito profundo podem causar problemas. Adicionar um limite configurável com `CompileException` descritiva.

---

### 4.3 Async/Await sem infraestrutura

O scanner reconhece `async` e `spawn` mas não há: rastreamento de funções assíncronas na SymbolTable, validação de contexto, ou emissão de código PHP com Fibers.

---

### 4.4 Source maps ausentes

Não há mapeamento entre posições no `.ps` e posições no `.php` gerado. Necessário para debugging útil conforme a linguagem se torna mais complexa.

---

### 4.5 Pattern matching e destructuring

Sem pattern matching a linguagem não acompanha alternativas modernas. Requer estrutura nova na AST.

---

## 5. Novas melhorias identificadas

### 5.1 Method chaining — bloqueador raiz no DotResolver

`DotResolver` (Statements) tem `resolve()` vazio. Isso impede que qualquer method chaining funcione fora do cenário muito específico de Array literal em `ProgramContext`. O foco da variável não é propagado após o `.`, fazendo o `FunctionCallResolver` sempre falhar e cair no `FunctionCallNotFoundResolver`.

**Impacto:** Bloqueia method chaining em todos os contextos (statement, assignment, multi-linha), typed collections, e chaining sobre retorno de construtor. Ver análise completa em `prompts/method-chaining-analysis.md`.

---

### 5.2 Typed collections sem suporte no parser

`Map<String>`, `Queue<Email>`, `Stack<Int>`, `List<Null|Int>` existem no Runtime com métodos definidos, mas a sintaxe `Type<SubType>` como expressão de atribuição nunca foi implementada no parser. Tokens `<` e `>` após um identifier de tipo precisam ser tratados como delimitadores de generic nesse contexto específico, não como operadores de comparação.

---

### 5.3 Arrow functions com emitter invertido

O emitter de arrow functions gera `function (price $, rate $)` ao invés de `function (float $price, float $rate)` — tipo e nome do parâmetro estão invertidos. Bug isolado no `ArrowFunctionEmitter`, independente de qualquer outra feature.

---

### 5.4 Compilação paralela de arquivos independentes

O `DependencyGraphBuilder` já identifica quais arquivos são independentes entre si (nós sem arestas entre eles no grafo). Esses arquivos poderiam ser compilados em paralelo via PHP `pcntl_fork()` ou Fibers, reduzindo o tempo de build em projetos com muitos arquivos sem dependência cruzada.

---

### 5.5 Melhor mensagem de erro no FunctionCallNotFoundResolver

Hoje: `This method "split" does not exist nor is supported for this type of variable`

Essa mensagem não diz qual variável, qual tipo foi inferido, nem sugere métodos disponíveis. Com o tipo em foco disponível, seria possível:

```
Method "split" not found on variable 'myString' (inferred type: String).
Available String methods: toLowerCase, toUpperCase, replace, contains?, split, join, ...
```

---

### 5.6 Language Server Protocol (LSP)

PHireScript não tem suporte a LSP. Um LSP server permitiria autocompletar, go-to-definition e diagnósticos em tempo real em qualquer editor. A infraestrutura de Scanner + Parser + SymbolTable já produz os dados necessários — faltaria expor via JSON-RPC.

---

### 5.7 Inferência de tipo mais profunda em atribuições

Hoje `var = other.method()` não rastreia o tipo de retorno como tipo de `var`. Isso impede que chamadas subsequentes em `var` encontrem os métodos corretos. A inferência precisa propagar o `returnOfPhpExecution` da `BaseMethods` para o `VariableDeclarationNode` da variável sendo atribuída.

---

### 5.8 Dead code detection

Com o grafo de dependências e a SymbolTable cross-file, seria possível detectar:
- Classes/interfaces declaradas mas nunca usadas (no `use` referenciando elas)
- Métodos declarados mas nunca chamados
- Variáveis atribuídas mas nunca lidas

Baixo risco de implementação (análise pós-bind), alto valor para o desenvolvedor.

---

## 6. Concluído

| Item original | O que foi feito |
|---|---|
| **1.1** SymbolTable — DIP violation | SymbolTable injetada via construtor no Checker |
| **1.2** Pipeline sem estado cross-file | Transpiler tem fases 0/1a/1/2 com SymbolTable global compartilhada |
| **1.5** Detecção de ciclos vazia | DependencyGraphBuilder tem `hasCycle()` com DFS e path legível |
| **2.2** Emitter dispatch O(n) | `EmitterDispatcher` tem `fastMap` lazy (O(1) no warm path, O(n) cold) |
| **3.4** Binder/Checker hardcoded | `PassDiscovery` com atributo `#[CompilerPass]` — auto-discovery implementado |
| **4.x** CacheManager | `CacheManager` implementado; `SymbolTableManager` usa para type methods |
| **5.1** Transpiler state sharing | SymbolTable global compartilhada via constructor injection |
| **5.6** Validação cross-file | Compilação two-phase: Phase 1 registra tipos, Phase 2 valida com tabela completa |
| **5.8** DFS cycle detection | Implementado (mesmo que 1.5) |
| **5.10** Auto-discovery de passes | `CompilerPass` attribute + `PassDiscovery` |
| Validator refatorado | `ValidatorRule` interface, regras separadas por responsabilidade |
| Checker refatorado | Sub-checkers extraídos, `CheckerException` corrigida para PHP 8.3 |
| Binder refatorado | Sub-binders com acesso a `globalTable` e `program` |
| Trait support | `TraitNode` + `TraitContext` + `TraitEmitter` |
| `if`/`elseif`/`else` | Parsing e emissão completos |
| Operadores de comparação | `==`, `===`, `!=`, `!==`, `>=`, `<=`, `>`, `<`, `&&`, `\|\|` |
