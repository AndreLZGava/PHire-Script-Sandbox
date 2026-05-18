# PHireScript — Revisão Arquitetural e Proposta de Melhorias

> Documento criado a partir da análise do `architecture.md`, código-fonte do compilador, e dos documentos `implementation.md` e `refactor.md`.

---

## Índice

1. [O que foi mal planejado ou executado](#1-o-que-foi-mal-planejado-ou-executado)
2. [Gargalos de processamento](#2-gargalos-de-processamento)
3. [Pontos que darão problema conforme a linguagem avança](#3-pontos-que-darão-problema-conforme-a-linguagem-avança)
4. [Proposta: CacheManager](#4-proposta-cachemanager)
5. [Outras melhorias arquiteturais](#5-outras-melhorias-arquiteturais)

---

## 1. O que foi mal planejado ou executado

### 1.1 SymbolTable duplicada e fraca

**Problema:** Existem **duas** SymbolTables com responsabilidades sobrepostas:
- `SymbolTable.php` (raiz) — usada pelo Binder/Checker, tem scopes, registra tipos e funções
- `SymbolTableManager.php` (Parser/Managers) — usada pelos Resolvers, carrega DefaultOverrideMethods via reflection

A `SymbolTable` da raiz é extremamente primitiva: registra builtins hardcoded (`toUpperCase → STRING`, `push → ARRAY`), não tem tipagem forte, e o método `getType()` usa `$linePosition` como chave secundária — uma decisão frágil que liga o tipo à posição no arquivo ao invés de ao escopo lógico.

**Impacto:**
- Não há uma fonte única de verdade sobre tipos no sistema
- O Checker recebe a SymbolTable tardiamente (via `check()`, não via construtor) — violação de DIP
- Adicionar novos builtins exige editar código hardcoded

**O que deveria ter sido:**
- Uma única SymbolTable com scoping hierárquico (lexical scoping)
- Tipos resolvidos por escopo, não por posição de linha
- Builtins registrados via configuração/discovery, não hardcoded

---

### 1.2 Pipeline sem estado compartilhado entre arquivos

**Problema:** O `Transpiler` cria instâncias novas de `SymbolTable`, `Binder`, `Checker` e `Emitter` **para cada arquivo** compilado. Não há estado compartilhado cross-file.

```php
// Transpiler.php — cada chamada a compile() cria tudo do zero
$symbolTable = new SymbolTable();
$binder = new Binder($symbolTable);
$checker = new Checker();
$emitter = new Emitter($this->config, $this->dependencyManager);
```

**Impacto:**
- O `TypeRegistrationBinder` registra classes/interfaces na SymbolTable, mas essa tabela morre ao final de cada arquivo
- Cross-file type checking é impossível — se `ClassA` usa `ClassB`, o Checker não sabe nada sobre `ClassB`
- O `DependencyGraphBuilder` constrói o grafo mas ninguém consome a ordem de compilação

**O que deveria ter sido:**
- SymbolTable global compartilhada entre todos os arquivos
- Compilação em duas fases: (1) registro de tipos de todos os arquivos, (2) compilação completa na ordem topológica

---

### 1.3 Dupla compilação para dependency graph

**Problema:** O `Compiler.compile()` faz:
1. `loader->load()` — escaneia + parse parcial de TODOS os arquivos via `TranspilerDependencyTree` para construir o grafo
2. `loader->loadAndCompile()` — escaneia + parse + bind + check + emit de TODOS os arquivos novamente

Cada arquivo `.ps` é escaneado e parseado **duas vezes** em cada build.

```php
// Compiler.php
$listPrograms = $this->loader->load($sourceDir, $transpilerDependencyTree);  // 1ª vez
$this->dependencyManager->buildGraph($listPrograms, $config);
$this->loader->loadAndCompile($sourceDir, $distDir, $transpiler);             // 2ª vez
```

**Impacto:** Tempo de build duplicado para projetos grandes.

---

### 1.4 SymbolTableManager usa reflection pesada no construtor

**Problema:** Toda vez que um `Parser` é instanciado, o `SymbolTableManager` no construtor:
1. Faz `glob()` no diretório `DefaultOverrideMethods/Types/`
2. Faz `require_once` de cada arquivo
3. Usa `get_declared_classes()` antes/depois para detectar classes novas
4. Instancia cada classe via reflection
5. Invoca cada método público para extrair `BaseMethods`

Isso acontece **para cada arquivo `.ps`** compilado.

**Impacto:**
- O `glob()` + `require_once` + reflection é O(n) onde n = número de types
- Em projetos com muitos arquivos, esse custo se multiplica
- O resultado é sempre o mesmo — é trabalho 100% repetido

---

### 1.5 Detecção de ciclos não implementada

**Problema:** O `DependencyGraphBuilder::validateGraph()` tem um comentário `// Here i'll implement cycles check using DFS` mas o corpo está vazio. O `topologicalSort()` detecta ciclos indiretamente (contando nós), mas não reporta qual é o ciclo.

**Impacto:** Mensagem de erro inútil (`"Cyclic dependency found!"`) sem indicar os arquivos envolvidos.

---

### 1.6 Exceptions genéricas em todo o compilador

**Problema:** Apesar de existirem `CompileException`, `CheckerException` e `FatalErrorException`, o código usa `throw new \Exception(...)` em vários lugares críticos:
- `DependencyGraphBuilder` (linhas 49, 80, 93, 156)
- `PhpFileGeneratorHandler` (linhas 40, 42)
- `TranspilerDependencyTree::getCodeBeforeGenerator()` acessa propriedade possivelmente não inicializada

**Impacto:** Impossível fazer tratamento de erro granular; tudo cai no mesmo `catch (Exception)`.

---

### 1.7 FileWatcher com polling ineficiente

**Problema:** O `FileWatcher` usa um loop `while(true)` com `usleep(900000)` (0.9s). A cada iteração:
- Recria `RecursiveDirectoryIterator` e `RecursiveIteratorIterator`
- Itera TODOS os arquivos do diretório
- Calcula `md5_file()` de cada arquivo `.ps`
- Chama `clearstatcache()` globalmente

**Impacto:** Em projetos grandes, o watcher consome CPU desnecessariamente. Não usa `inotify` (Linux) ou FSEvents apesar da arquitetura mencionar suporte.

---

## 2. Gargalos de processamento

### 2.1 Scanner — regex sequencial

O Scanner testa **cada token** contra **todas as regex patterns** em ordem sequencial. Para cada posição do cursor, ele tenta ~25 patterns até encontrar match.

**Custo:** O(tokens × patterns). Com ~25 patterns e um arquivo de 500 linhas (~2000 tokens), são ~50.000 tentativas de regex por arquivo.

**Otimização possível:**
- Classificar o primeiro caractere e pular direto para o grupo relevante de patterns (dispatch table)
- Combinar patterns relacionados em uma única alternation regex
- Cachear tokens por arquivo (ver CacheManager)

---

### 2.2 Emitter — dispatch linear O(n)

O `EmitterDispatcher` itera por **39 emitters** testando `supports()` em cada um até encontrar o correto. Para cada nó da AST, isso é O(39).

```php
// EmitterDispatcher — para cada nó, testa todos
foreach ($this->emitters as $emitter) {
    if ($emitter->supports($node, $ctx)) {
        return $emitter->emit($node, $ctx);
    }
}
```

**Otimização possível:**
- Usar um mapa `className → emitter` indexado pelo tipo do nó
- `$emitters[get_class($node)]->emit($node, $ctx)` seria O(1)

---

### 2.3 ClassScanner — token_get_all em cada arquivo

`ClassScanner::listClassesExtending()` usa `token_get_all()` do PHP para parsear cada arquivo `.php` no diretório de tipos. Isso é feito a cada build para descobrir MetaTypes e SuperTypes.

**Custo:** Proporcional ao número e tamanho dos arquivos de tipo.

**Otimização:** O resultado é estático — deveria ser cacheado ou resolvido em build-time.

---

### 2.4 PhpFileGeneratorHandler — segunda AST parse

Após o Emitter gerar o código PHP como string, o `PhpFileGeneratorHandler` faz **parse dessa string novamente** usando `nikic/php-parser`, aplica visitors, e reformata.

**Problema:** O compilador já tem uma AST completa. Gerar string para re-parsear é um desperdício.

**Alternativa de longo prazo:** Emitir diretamente nós `nikic/php-parser` ao invés de strings, eliminando o round-trip string → AST → string.

---

### 2.5 `php -l` chamado para cada arquivo compilado

O `FileCompiler::compileFile()` executa `exec("php -l $output")` para cada arquivo gerado. Isso fork()a um processo PHP por arquivo.

**Custo:** ~50ms por arquivo (fork + startup do PHP).

**Otimização:** Validar apenas em modo debug, ou fazer validação batch no final do build.

---

### 2.6 TokenManager::getNextAfterFirstFoundElement — scan de 1000 tokens

```php
public function getNextAfterFirstFoundElement($elementsAsValue)
{
    $leftTokens = $this->getLeftTokens(1000);  // copia 1000 tokens
    foreach ($leftTokens as $key => $token) { ... }
}
```

Cria uma cópia de até 1000 tokens via `array_slice` só para buscar um elemento. Deveria usar indexação direta.

---

## 3. Pontos que darão problema conforme a linguagem avança

### 3.1 Union types não suportados

O `implementation.md` marca union types (`Int|String`) como 🚫. Conforme a linguagem cresce, a falta de union types vai bloquear:
- Retornos nullable (`String|Null`)
- Parâmetros polimórficos
- Generics com bounds (`List<String|Int>`)

A SymbolTable não tem infraestrutura para representar tipos compostos.

---

### 3.2 Escopo plano no VariableManager

O `VariableManager` usa um array simples `$variables = []` sem hierarquia de escopos. Variáveis de escopos internos (if, loop, closure) existem no mesmo namespace que variáveis externas.

**Vai quebrar com:**
- Closures que capturam variáveis (shadowing)
- Loops aninhados com variáveis de iteração
- Block scoping (se implementado)

---

### 3.3 ContextManager sem limite de profundidade

O stack de contexts não tem limite. Um arquivo malicioso com nested structures pode causar stack overflow. Não há proteção contra recursão infinita nos resolvers.

---

### 3.4 Binder/Checker sem extensibilidade por plugins

Binder e Checker criam suas listas de implementações hardcoded no construtor. Para adicionar um novo binder ou checker, é preciso editar o arquivo orquestrador.

**Problema futuro:** Quando a linguagem tiver plugins, decoradores, ou macros, cada feature nova exige modificar `Binder.php` e `Checker.php`.

**Solução:** Registry pattern com auto-discovery ou configuração.

---

### 3.5 Generics superficiais

`List<T>`, `Map<T>`, `Queue<T>`, `Stack<T>` existem como nodes dedicados, mas:
- Não há validação de tipo do elemento em tempo de bind
- Não há propagação de tipo genérico (se `List<String>.map()` retorna `List<T>`, qual é T?)
- Adicionar novos genéricos exige criar Node + Context + Resolver + Emitter + Checker

---

### 3.6 Async/Await é sketch sem base

O scanner reconhece `async` e `spawn`, mas não há infraestrutura para:
- Rastrear funções assíncronas na SymbolTable
- Validar que `spawn` só é usado em contexto async
- Emitir código PHP com Fibers/ReactPHP

---

### 3.7 Ausência de source maps

Não há mapeamento entre posições no `.ps` e posições no `.php` gerado. Conforme a linguagem fica mais complexa, debugging fica impossível.

---

### 3.8 Pattern matching e destructuring inexistentes

Sem pattern matching, a linguagem não consegue competir com alternativas modernas. A AST atual não tem estrutura para representar patterns.

---

## 4. Proposta: CacheManager

### 4.1 Visão geral

O `CacheManager` seria uma classe central responsável por:
1. Cachear resultados intermediários do compilador em disco (`.cache/`)
2. Invalidar cache granularmente quando arquivos fonte mudam
3. Integrar-se com o `DependencyGraphBuilder` para invalidação em cascata
4. Servir dados para qualquer classe do pipeline que precise de informações já computadas

```
Projeto/
├── .cache/                          # ignorado pelo .gitignore
│   ├── manifest.json                # índice geral: hash de cada arquivo fonte
│   ├── types/
│   │   ├── ArrayMethods.cache       # métodos serializados de ArrayMethods
│   │   ├── StringMethods.cache      # métodos serializados de StringMethods
│   │   ├── StackMethods.cache
│   │   └── ...
│   ├── ast/
│   │   ├── <hash_arquivo1>.ast      # AST serializada de cada .ps
│   │   └── <hash_arquivo2>.ast
│   ├── tokens/
│   │   ├── <hash_arquivo1>.tokens   # tokens serializados de cada .ps
│   │   └── <hash_arquivo2>.tokens
│   ├── graph/
│   │   └── dependency_graph.cache   # grafo de dependências serializado
│   └── config/
│       └── compiler_config.cache    # config + metatypes + supertypes cacheados
```

---

### 4.2 Interface proposta

```php
class CacheManager
{
    private string $cacheDir;
    private array $manifest = [];     // hash de cada arquivo monitorado
    private bool $enabled = true;

    public function __construct(string $projectRoot)
    {
        $this->cacheDir = $projectRoot . '/.cache';
        $this->ensureCacheDir();
        $this->loadManifest();
    }

    // --- Verificação de validade ---
    public function isValid(string $filePath): bool;           // hash do arquivo mudou?
    public function invalidate(string $filePath): void;        // invalida cache de um arquivo
    public function invalidateCascade(string $filePath): void; // invalida arquivo + dependentes

    // --- Type Methods Cache ---
    public function getTypeMethods(string $typeName): ?array;  // ex: 'ArrayMethods'
    public function setTypeMethods(string $typeName, array $methods): void;
    public function areTypeMethodsValid(): bool;               // arquivos .php de types mudaram?

    // --- AST Cache ---
    public function getAst(string $filePath): ?Program;
    public function setAst(string $filePath, Program $ast): void;

    // --- Token Cache ---
    public function getTokens(string $filePath): ?array;
    public function setTokens(string $filePath, array $tokens): void;

    // --- Dependency Graph Cache ---
    public function getDependencyGraph(): ?array;
    public function setDependencyGraph(array $graph): void;

    // --- Config Cache ---
    public function getConfig(): ?array;
    public function setConfig(array $config): void;

    // --- Lifecycle ---
    public function flush(): void;          // limpa todo o cache
    public function persist(): void;        // salva manifest em disco
}
```

---

### 4.3 Como o CacheManager se integra com cada parte do pipeline

#### SymbolTableManager (maior benefício)

O `SymbolTableManager` hoje faz reflection pesada **para cada arquivo**. Com cache:

```php
// ANTES (atual) — reflection toda vez
public function __construct()
{
    $targetDir = __DIR__ . '/../../../Runtime/DefaultOverrideMethods/Types';
    $this->typeDefinitions = $this->scanAndBuildRegistry($targetDir);
}

// DEPOIS — com CacheManager
public function __construct(CacheManager $cache)
{
    if ($cache->areTypeMethodsValid()) {
        $this->typeDefinitions = $cache->getAllTypeMethods();
    } else {
        $this->typeDefinitions = $this->scanAndBuildRegistry($targetDir);
        $cache->setAllTypeMethods($this->typeDefinitions);
    }
}
```

**Economia:** O scan de types é feito 1 vez, depois lido do cache em todas as builds subsequentes. Só invalida quando um arquivo em `DefaultOverrideMethods/Types/` muda.

#### Scanner + Parser (build incremental)

```php
// ANTES — escaneia + parseia tudo sempre
$scanner = new Scanner($code, $path);
$tokens = $scanner->tokenize();

// DEPOIS — com CacheManager
if ($cache->isValid($path)) {
    $tokens = $cache->getTokens($path);
    $ast = $cache->getAst($path);
} else {
    $tokens = (new Scanner($code, $path))->tokenize();
    $cache->setTokens($path, $tokens);
    $ast = $parser->parse($tokens, $path);
    $cache->setAst($path, $ast);
}
```

#### DependencyGraphBuilder

O grafo só muda quando `use`/`pkg` statements mudam. Cache evita reconstrução:

```php
if ($cache->getDependencyGraph() && $cache->allFilesValid($sourceFiles)) {
    $graph = $cache->getDependencyGraph();
} else {
    $graph = $this->buildGraph($astList, $config);
    $cache->setDependencyGraph($graph);
}
```

#### FileWatcher (watch mode)

O watcher já mantém hashes em memória. O CacheManager persistiria esses hashes, permitindo que um `watch` reiniciado não recompile tudo:

```php
// Ao iniciar watch, carregar hashes do cache
$filesHash = $cache->getFileHashes() ?? [];

// Ao detectar mudança, invalidar em cascata
$cache->invalidateCascade($changedFile);
// Recompilar apenas os arquivos invalidados
```

#### FileManager::getConfigFile()

O `ClassScanner` que descobre MetaTypes e SuperTypes seria cacheado:

```php
if ($cache->getConfig()) {
    return $cache->getConfig();
}
// ... discovery normal ...
$cache->setConfig($configs);
```

---

### 4.4 Invalidação em cascata via DependencyGraph

Quando um arquivo `.ps` muda, o CacheManager consulta o `DependencyGraphBuilder` para descobrir quem depende dele e invalida em cascata:

```
Arquivo A (mudou)
  └── usado por B → invalida cache de B
       └── usado por C → invalida cache de C
```

Quando um arquivo do PHireScript muda (ex: `StringMethods.php`):
- Invalida cache de type methods
- Invalida cache de TODOS os arquivos `.ps` (pois qualquer um pode usar String methods)

---

### 4.5 Impacto estimado

| Cenário | Sem cache | Com cache |
|---------|-----------|-----------|
| Build completo (1ª vez) | 100% do tempo | ~100% + overhead de escrita (~5%) |
| Build após mudar 1 arquivo | 100% do tempo | ~5-15% (só recompila arquivo + dependentes) |
| Watch — arquivo detectado | Re-scan + re-parse + re-bind + re-check + re-emit | Só as fases necessárias |
| SymbolTableManager init | Reflection de ~20 classes × N arquivos | 1 leitura de cache |
| ClassScanner (config) | token_get_all de ~30 arquivos | 1 leitura de cache |

---

## 5. Outras melhorias arquiteturais

### 5.1 Compilação incremental sem CacheManager

Mesmo sem cache em disco, o `Transpiler` deveria reusar objetos entre arquivos:

```php
// Proposta: Transpiler com estado compartilhado
class Transpiler
{
    private SymbolTable $globalSymbolTable;
    private SymbolTableManager $typeRegistry; // criado 1x, reusado

    public function __construct(...)
    {
        $this->globalSymbolTable = new SymbolTable();
        $this->typeRegistry = new SymbolTableManager(); // reflection 1x só
    }

    public function compile(string $code, string $path): string
    {
        // Reutiliza $this->typeRegistry e $this->globalSymbolTable
    }
}
```

---

### 5.2 Emitter dispatch via mapa de tipos

```php
// ANTES — O(n) linear scan
foreach ($this->emitters as $emitter) {
    if ($emitter->supports($node, $ctx)) { ... }
}

// DEPOIS — O(1) lookup
class EmitterDispatcher
{
    private array $map = []; // className => NodeEmitter

    public function __construct(array $emitters)
    {
        foreach ($emitters as $emitter) {
            // Cada emitter declara que tipo de nó suporta
            $this->map[$emitter->getNodeClass()] = $emitter;
        }
    }

    public function emit(object $node, EmitContext $ctx): string
    {
        $class = get_class($node);
        if (!isset($this->map[$class])) {
            throw new CompileException("No emitter for {$class}");
        }
        return $this->map[$class]->emit($node, $ctx);
    }
}
```

---

### 5.3 Scanner com dispatch table

```php
// Agrupar patterns por primeiro caractere
private const CHAR_DISPATCH = [
    '/' => ['T_COMMENT'],           // comentários começam com /
    '"' => ['T_STRING_LIT'],        // strings começam com " ou '
    "'" => ['T_STRING_LIT'],
    '0'...'9' => ['T_RANGE', 'T_NUMBER'],  // números
    // letras → keywords, identifiers, etc.
];

// No loop de tokenize(), verificar primeiro caractere antes de testar regex
$firstChar = $snippet[0];
$candidates = self::CHAR_DISPATCH[$firstChar] ?? array_keys(self::PATTERNS);
foreach ($candidates as $type) { ... }
```

---

### 5.4 VariableManager com escopo hierárquico

```php
class VariableManager
{
    private array $scopeStack = [[]]; // pilha de escopos

    public function enterScope(): void
    {
        $this->scopeStack[] = [];
    }

    public function exitScope(): void
    {
        array_pop($this->scopeStack);
    }

    public function getVariable(string $name): ?VariableDeclarationNode
    {
        // Busca do escopo mais interno para o mais externo
        for ($i = count($this->scopeStack) - 1; $i >= 0; $i--) {
            if (isset($this->scopeStack[$i][$name])) {
                return $this->scopeStack[$i][$name];
            }
        }
        return null;
    }
}
```

---

### 5.5 PhpFileGeneratorHandler — eliminar double parse

**Curto prazo:** Cachear o `ParserFactory` e `PrettyPrinter` (já feito parcialmente).

**Longo prazo:** Modificar os Emitters para gerar nós `PhpParser\Node` ao invés de strings. Isso eliminaria o round-trip completo:

```
ATUAL:   AST PHireScript → string PHP → parse nikic → AST PHP → string PHP
IDEAL:   AST PHireScript → AST PHP → string PHP
```

---

### 5.6 Validação de tipos cross-file

O `Checker` atual não pode validar referências entre arquivos. Proposta:

1. **Fase 1 (registro):** Compilar todos os arquivos até o Binder, coletando tipos na SymbolTable global
2. **Fase 2 (verificação):** Rodar Checker com acesso à SymbolTable completa
3. **Fase 3 (emissão):** Emitir código PHP

---

### 5.7 Error recovery no Parser

O parser atual lança `CompileException` no primeiro erro e aborta. Para UX de qualidade:
- Acumular erros e continuar parseando
- Reportar todos os erros de um arquivo de uma vez
- Em modo watch, mostrar erros sem matar o processo

---

### 5.8 Dependency Graph — detecção de ciclos com DFS

```php
private function validateGraph(): void
{
    $visited = [];
    $stack = [];

    foreach (array_keys($this->nodes) as $pkg) {
        if ($this->hasCycle($pkg, $visited, $stack, $cycle)) {
            $path = implode(' → ', $cycle);
            throw new CompileException(
                "Circular dependency detected: {$path}"
            );
        }
    }
}

private function hasCycle(string $node, array &$visited, array &$stack, array &$cycle): bool
{
    $visited[$node] = true;
    $stack[$node] = true;

    foreach ($this->nodes[$node]->dependsOn as $dep) {
        if (!isset($visited[$dep]) && $this->hasCycle($dep, $visited, $stack, $cycle)) {
            $cycle[] = $dep;
            return true;
        }
        if (isset($stack[$dep])) {
            $cycle = [$dep, $node];
            return true;
        }
    }

    unset($stack[$node]);
    return false;
}
```

---

### 5.9 TokenManager — eliminar cópia de arrays

`getLeftTokens()` e `getProcessedTokens()` usam `array_slice` que copia o array. Use `ArrayIterator` ou acesso direto por índice:

```php
public function peekRange(int $start, int $count): \Generator
{
    $end = min($start + $count, count($this->tokens));
    for ($i = $start; $i < $end; $i++) {
        yield $this->tokens[$i];
    }
}
```

---

### 5.10 Binder/Checker — auto-discovery de implementações

```php
// Em vez de listar manualmente no construtor:
$this->binders = [
    new TypeRegistrationBinder(),
    new ProgramBinder(),
    // ... 11 binders hardcoded
];

// Auto-discovery com atributo PHP 8.1:
#[CompilerPass(order: 1)]
class TypeRegistrationBinder implements BinderInterface { }

// No construtor do Binder:
$this->binders = (new PassDiscovery(__DIR__ . '/Binder'))
    ->discover()
    ->sortByOrder();
```

---

## Resumo de prioridades

| Prioridade | Melhoria | Impacto | Esforço |
|-----------|----------|---------|---------|
| 🔴 Alta | CacheManager para SymbolTableManager | Elimina reflection repetida | Médio |
| 🔴 Alta | Eliminar dupla compilação (load + loadAndCompile) | -50% tempo de build | Médio |
| 🔴 Alta | SymbolTable global cross-file | Habilita type-checking real | Alto |
| 🟡 Média | Emitter dispatch via mapa O(1) | Performance do emitter | Baixo |
| 🟡 Média | Scanner dispatch table | Performance do scanner | Médio |
| 🟡 Média | CacheManager para build incremental | Builds rápidos em watch | Alto |
| 🟡 Média | VariableManager com scoping | Corretude de variáveis | Médio |
| 🟢 Baixa | Error recovery no parser | UX do desenvolvedor | Alto |
| 🟢 Baixa | Eliminar double parse (Emitter → nikic) | Performance do processamento | Muito Alto |
| 🟢 Baixa | Auto-discovery de binders/checkers | Manutenibilidade | Baixo |
