# Research: PHP Interop — External Class Import and Validation

**Feature**: `002-php-interop-import` | **Date**: 2026-05-30

---

## Compilador — Estado Atual do `external`

### O que já funciona

- `ExternalNode`, `ExternalContext`, `ExternalResolver`, `ExternalEmitter` existem e estão integrados ao pipeline.
- `ExternalEmitter` emite `use ClassName [as Alias];` corretamente.
- O Scanner reconhece a keyword `external`.
- `ExternalContext` suporta namespace com backslash, alias com `as`, e group use com `{}`.

### O que não existe

- Nenhuma inspeção Reflection da classe external em nenhuma fase.
- Nenhum registro no `SymbolTable` do que a classe oferece.
- `PropertyAccessEmitter` emite sempre `->` independente de ser external, estático ou constante.
- Não existe `ExternalInstantiationNode` nem tratamento de `ClassName()` para externals.
- Nenhum Binder processa `ExternalNode`.
- Nenhum Checker valida membros externos.

---

## Padrões Identificados no Compilador

### Registro de tipos no SymbolTable

`TypeRegistrationBinder` (order: 1) itera `Program::$statements`, detecta `ClassNode | InterfaceNode`, e chama `$binder->globalTable->registerTypeDefinition($name, $node)`. O mesmo padrão será seguido por `ExternalBinder` para registrar `ExternalClassDescriptor`.

### Validação via Checker

Checkers usam `#[CompilerPass(order: N)]` e implementam `mustCheck(Node): bool` + `check(Node, Checker): void`. `ProgramChecker` (order: 4) delega para todos os checkers registrados. Novos checkers são auto-descobertos via `PassDiscovery` (usa `ReflectionClass` para encontrar classes com o atributo `#[CompilerPass]`).

### Emitters

`EmitterDispatcher` itera os emitters registrados em `Emitter.php` na ordem em que estão no array. `PropertyAccessEmitter` é o responsável por `PropertyAccessNode`. Será modificado para verificar se o objeto é um external e delegar a lógica de emissão correta.

### SymbolTable

- `setType(string $name, mixed $type)` e `getType(string $name)` — escopos empilhados.
- `registerTypeDefinition(string $name, $node)` e `getTypeDefinition(string $name)` — registro global de tipos PHireScript.
- Não existe `registerExternal` — será adicionado. Manter separado de `typeDefinitions` para não colidir com classes PHireScript nativas (FR-014).

---

## ReflectionClass — Capacidades Relevantes

| Necessidade | API PHP |
|-------------|---------|
| Verificar se método é estático | `ReflectionMethod::isStatic()` |
| Verificar visibilidade | `ReflectionMethod::isPublic()` |
| Obter tipo de retorno | `ReflectionMethod::getReturnType()` → `ReflectionNamedType | ReflectionUnionType | null` |
| Listar constantes públicas | `ReflectionClass::getConstants(ReflectionClassConstant::IS_PUBLIC)` |
| Verificar construtor | `ReflectionClass::getConstructor()` → `ReflectionMethod | null` |
| Parâmetros obrigatórios do construtor | `ReflectionParameter::isOptional()` |
| Propriedades públicas | `ReflectionClass::getProperties(ReflectionProperty::IS_PUBLIC)` |
| Verificar se classe existe | `class_exists($fqcn, true)` antes de `new ReflectionClass($fqcn)` |

### Union Types (`PDOStatement|false`)

`ReflectionMethod::getReturnType()` retorna `ReflectionUnionType` para tipos union. `ReflectionUnionType::getTypes()` retorna array de `ReflectionNamedType`. Para a propagação de tipo no SymbolTable, será armazenado o array de nomes de tipos. O Checker emite warning (FR-012) quando um método é acessado e existe apenas em parte dos tipos da union.

### Retorno `mixed` / sem tipo

`ReflectionMethod::getReturnType()` retorna `null` quando não há tipo declarado. `ReflectionNamedType::getName()` retorna `'mixed'` para o tipo `mixed`. Em ambos os casos, registrar `'MIXED_EXTERNAL'` no SymbolTable e emitir warning (FR-015).

---

## Análise dos Cases de Feature

### Case 5 — `ExternalCallingConstants.psc`

```php
use DateTime as DateTimePhp;
$date = DateTimePhp::createFromFormat('d/m/Y', '25/12/2023');
\print_r($date->format(DateTimePhp::ATOM));
```

- `createFromFormat` → estático → `DateTimePhp::createFromFormat(...)`
- `DateTimePhp.ATOM` → constante → `DateTimePhp::ATOM` (argumento de `format`)
- `date.format(...)` → `$date` é variável de tipo `DateTime`, `format` é método de instância → `$date->format(...)`
- `.display()` → **fora de escopo**, remover do case de validação

### Case 13 — `ExternalCallingChainningMethods.psc`

```php
use DateTime as DateTimePhp;
$date = (new DateTimePhp())->modify('+3 days')
->modify('+2 hours')
->format('d/m/Y H:i');

\print_r($date);
```

- `DateTimePhp()` → instanciação → `new DateTimePhp()`
- Encadeamento direto sobre `(new DateTimePhp())` no `.psc` — porém o `.ps` do case original (recuperado do git) mostrava `date = DateTimePhp()` seguido de encadeamento.
- **Decisão de escopo**: o case de validação usará a forma com variável intermediária (US4) — `date = DateTimePhp()` e depois `result = date.modify(...).modify(...).format(...)` — conforme acordado.
- `.display()` → **fora de escopo**

### Case 15 — `ExternalCallingStaticMethods.psc`

```php
use PDO;
$availableDrivers = PDO::getAvailableDrivers();
\print_r($availableDrivers);
$query = (new PDO())->query("SELECT id, name, email FROM user LIMIT 1");
$user = $query->fetchObject();
if ($user) {
    \print_r($user->id);
    \print_r($user->name);
    \print_r($user->email);
}
```

- `PDO.getAvailableDrivers()` → estático → `PDO::getAvailableDrivers()`
- `PDO.query(...)` → instância em class name → `(new PDO())->query(...)` — Checker emitirá warning sobre construtor PDO ter parâmetros obrigatórios (FR-009 → warning neste caso, pois o exemplo é pedagógico)
- `query.fetchObject()` → `$query` é `PDOStatement|false`, `fetchObject` em `PDOStatement` → warning de union type
- `user.id`, `user.name`, `user.email` → propriedades de objeto externo → `$user->id`, `$user->name`, `$user->email` (sem validação de propriedade nesta versão)
- `.display()` → **fora de escopo**, substituídas por `\print_r()`

---

## Riscos Identificados

| Risco | Impacto | Mitigação |
|-------|---------|-----------|
| Classe externa não no autoloader em compile-time | Alto — erro de compilação inesperado para o dev | FR-008: mensagem clara com nome da classe e sugestão de verificar Composer |
| `PDO` sem DSN no construtor — exemplo pedagógico vs erro real | Médio | FR-009: warning (não erro hard) para o case de exemplo; erro hard apenas quando há parâmetros sem default |
| `PropertyAccessEmitter` modificado quebra casos existentes | Alto | Testes unitários existentes em `PropertyAccessEmitterTest.php` devem continuar passando |
| Encadeamento multi-linha (`.` no início da linha) não suportado pelo Parser | Médio | FR-013: erro claro; casos de validação usam variável intermediária |
| `ReflectionClass` de interfaces sem construtor | Baixo | Tratar `getConstructor() === null` como construtor público sem parâmetros |
