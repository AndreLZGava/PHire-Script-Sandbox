# PHireScript — Mapa de Implementação

Paralelo entre o que existe em PHP e o status de cada funcionalidade no PHireScript (que compila para PHP).

**Legenda:**
- ✅ **Funcional** — implementado, coberto por case no sandbox
- ⚠️ **Parcial** — compila em casos básicos, lacunas conhecidas
- ❌ **Sketch** — esqueleto existe (scanner/contexto/emitter) mas não é utilizável
- 🚫 **Não implementado** — não existe em nenhuma camada

---

## 1. Módulos e Imports

| PHP | PHireScript | Status | Sandbox case |
|-----|-------------|--------|--------------|
| `namespace Foo\Bar;` | `pkg PHireScript.Samples1` | ✅ | case_1 |
| `use Foo\Bar\Baz;` | `use PHireScript.Samples1.Baz` | ✅ | case_1 |
| `use Foo\Bar\{A, B, C};` | `use PHireScript.Samples1.{A, B}` | ✅ | case_2 |
| `use Foo\Bar\Baz as Alias;` | `use ... as Alias` | ✅ | — |
| `use ExternalClass;` (de lib PHP) | `external ExternalClass` | ⚠️ | — |

> **`external`:** gera um `use` PHP correto, mas instanciar ou chamar métodos de classes externas não funciona — a emissão para além do `use` não está implementada.

---

## 2. Variáveis e Literais

| PHP | PHireScript | Status | Sandbox case |
|-----|-------------|--------|--------------|
| `$x = "hello";` | `x: String = "hello"` | ✅ | case_13 |
| `$x = 42;` | `x: Int = 42` | ✅ | case_17 |
| `$x = 3.14;` | `x: Float = 3.14` | ✅ | case_16 |
| `$x = true;` | `x: Bool = true` | ✅ | case_15 |
| `$x = null;` | `x: Null = null` | ✅ | case_13 |
| `$x = ['a' => 1];` | `x: Object = { a: 1 }` | ✅ | case_18 |
| `$x = [1, 2, 3];` | `x: Array = [1, 2, 3]` | ✅ | case_14 |
| `range(1, 10)` | `1..10` | ✅ | case_12 |
| `const FOO = 42;` (global) | `const FOO = 42` | ✅ | case_10 |
| `// comment` / `/* */` | `// comment` / `/* */` | ✅ | — |

---

## 3. Primitivos e Tipos Nativos

| PHP | PHireScript | Status |
|-----|-------------|--------|
| `string` | `String` | ✅ |
| `int` | `Int` | ✅ |
| `float` | `Float` | ✅ |
| `bool` | `Bool` | ✅ |
| `null` | `Null` | ✅ |
| `void` | `Void` | ✅ |
| `mixed` | `Mixed` / `Any` | ✅ |
| `array` | `Array` | ✅ |
| `object` | `Object` | ✅ |
| `int\|string` (union type) | — | 🚫 |

---

## 4. Estruturas de Controle

| PHP | PHireScript | Status | Sandbox case |
|-----|-------------|--------|--------------|
| `if (...) { }` | `if (...) { }` | ✅ | case_31 |
| `if (...) { } else { }` | `if (...) { } else { }` | ✅ | case_32 |
| `elseif` | `elseif` | ✅ | case_34 |
| `try { } catch { } finally { }` | `try { } handle { } always { }` | ✅ | case_9 |
| `throw new Exception(...)` | `throw new Exception(...)` | ✅ | — |
| `foreach ($x as $k => $v) { }` | `loop` | ❌ | — |
| `for ($i = 0; ...) { }` | — | 🚫 | — |
| `while (...) { }` | — | 🚫 | — |
| `switch (...) { case: }` | `switch` | ❌ | — |
| `match (...)` | — | 🚫 | — |

> **`loop`:** contexto (`LoopContext`) existe no parser, mas emitter não implementado.
> **`switch`:** contexto (`SwitchStatementContext`) existe no parser, mas emitter não implementado.

---

## 5. Funções

| PHP | PHireScript | Status |
|-----|-------------|--------|
| `function foo(T $x): T { }` | `function foo(x: T): T { }` | ⚠️ |
| `fn($x) => $x * 2` | `($x) => $x * 2` | ⚠️ |
| `return $x;` | `return x` | ✅ |

> **Funções e arrow functions:** casos básicos compilam, mas casos-limite (closures em expressões, recursão, funções de ordem superior) não têm cobertura e podem falhar.

---

## 6. Classes e OOP — Declarações

| PHP | PHireScript | Status | Sandbox case |
|-----|-------------|--------|--------------|
| `class Foo { }` | `class Foo { }` | ✅ | case_3 |
| `abstract class Foo { }` | `abstract class Foo { }` | ✅ | case_4 |
| `final class Foo { }` | — | 🚫 | — |
| `interface Foo { }` | `interface Foo { }` | ✅ | case_1 |
| `trait Foo { }` | `trait Foo { }` | ✅ | case_29 |
| `readonly class Foo { }` | — | 🚫 | — |
| DTO / value object | `type Foo as scoped { }` | ✅ | case_5 |
| Objeto imutável | `immutable Foo as scoped { }` | ✅ | case_6 |
| `enum Foo { }` | `enum` | ❌ | — |

> **Escopos de classe** (`as scoped`, `as singleton`, `as newable`, `as transient`) são tokens reconhecidos e influenciam a emissão, mas a injeção de dependência que daria sentido a `singleton`/`scoped` não funciona ainda.

---

## 7. Classes — Membros

| PHP | PHireScript | Status | Sandbox case |
|-----|-------------|--------|--------------|
| `public string $x;` | `x: String` | ✅ | case_3 |
| `public readonly string $x` | `readonly x: String` | ⚠️ | — |
| `public static $x` | `static x: T` | ⚠️ | — |
| `public function foo(): T { }` | `function foo(): T { }` | ✅ | case_3 |
| `__construct` | `onCreate` | ✅ | case_3 |
| `__destruct` | `onDestroy` | ✅ | case_3 |
| `__get` | `onGet` | ✅ | case_3 |
| `__set` | `onSet` | ✅ | case_3 |
| `__isset` | `onHas` | ✅ | case_3 |
| `__unset` | `onUnset` | ✅ | case_3 |
| `__call` | `onCall` | ✅ | case_3 |
| `__callStatic` | `onStaticCall` | ✅ | case_3 |
| `__toString` | `toString` | ✅ | case_3 |
| `__serialize` | `toSerialize` | ✅ | case_3 |
| `__unserialize` | `toUnserialize` | ✅ | case_3 |
| `__sleep` | `beforeSerialize` | ✅ | case_3 |
| `__wakeup` | `afterUnserialize` | ✅ | case_3 |
| `__clone` | `onClone` | ✅ | case_3 |
| `__debugInfo` | `toInspect` | ✅ | case_3 |
| getter personalizado | `< getterFn` | ⚠️ | — |
| setter personalizado | `> setterFn` | ⚠️ | — |
| getter + setter | `<> fn` | ⚠️ | — |

> **Accessor syntax** (`<`, `>`, `<>`): o scanner reconhece os tokens `T_ACCESSORS`, mas a compilação tem lacunas nos casos de borda.

---

## 8. Classes — Relações

| PHP | PHireScript | Status | Sandbox case |
|-----|-------------|--------|--------------|
| `class Foo extends Bar` | `class Foo extends Bar` | ✅ | case_4 |
| `class Foo implements Bar` | `class Foo implements Bar` | ✅ | case_2 |
| `use TraitFoo;` (dentro de classe) | `with TraitFoo` | ✅ | case_28 |
| `interface Foo extends Bar` | `interface Foo extends Bar` | ✅ | case_2 |

---

## 9. Injeção de Dependência

| PHP | PHireScript | Status |
|-----|-------------|--------|
| Container DI (Laravel/Symfony) | `inject { dep: Type }` | ❌ |

> O campo `resolver` em `PHireScript.json` existe (`laravel`, `symfony`, `custom`) e a palavra-chave `inject` é reconhecida pelo scanner, mas o feature é um esqueleto — nenhuma saída PHP é gerada.

---

## 10. Coleções Tipadas

| PHP | PHireScript | Status | Sandbox case |
|-----|-------------|--------|--------------|
| `array` de tipo único | `List<T>` | ⚠️ | case_14 |
| `array` chave-valor tipado | `Map<T>` | ⚠️ | — |
| Fila (SplQueue) | `Queue<T>` | ⚠️ | — |
| Pilha (SplStack) | `Stack<T>` | ⚠️ | — |

> As declarações de tipo compilam (ex: `List<String>`), mas o comportamento runtime das coleções (push/pop, iteração, tipagem forte em runtime) não está completo. Há checker parcial para Queue.

---

## 11. SuperTypes (tipos de domínio — validação automática)

SuperTypes compilam para chamadas `TypeClass::cast($value)` no PHP gerado. Todo o runtime está implementado e testado em unit tests.

| SuperType PHireScript | Equivalente PHP (manual) | Status | Sandbox case |
|-----------------------|--------------------------|--------|--------------|
| `Email` | `filter_var($v, FILTER_VALIDATE_EMAIL)` | ✅ | case_22 |
| `Uuid` | regex RFC 4122 + geração v4 | ✅ | case_26 |
| `Url` | `filter_var($v, FILTER_VALIDATE_URL)` | ✅ | case_25 |
| `Slug` | regex + transliteração | ✅ | case_24 |
| `Color` | regex hex 3/6 dígitos | ✅ | case_19 |
| `Json` | `json_decode` | ✅ | case_23 |
| `Cron` | parser de expressão cron | ✅ | case_20 |
| `Duration` | parser `"1h 30m"` → segundos | ✅ | case_21 |
| `Ipv4` | `filter_var FILTER_FLAG_IPV4` | ✅ | — |
| `Ipv6` | `filter_var FILTER_FLAG_IPV6` | ✅ | — |
| `Mac` | `filter_var FILTER_VALIDATE_MAC` | ✅ | — |
| `CardNumber` | algoritmo de Luhn | ✅ | — |
| `Cvv` | regex 3-4 dígitos | ✅ | — |
| `ExpiryDate` | MM/YY + validação de expiração | ✅ | — |

---

## 12. MetaTypes (tipos de domínio — objetos de valor)

MetaTypes são objetos que encapsulam um valor (implementam `Stringable`). O runtime está implementado. O suporte do compilador é **parcial**: o scanner reconhece os tokens (`T_META_TYPE`), há `MetaTypeCastingResolver` e `CastingEmitter`, mas não há cases no sandbox validando a compilação.

| MetaType PHireScript | Equivalente PHP (manual) | Runtime | Compilador |
|----------------------|--------------------------|---------|------------|
| `Currency` | `NumberFormatter` + centavos | ✅ | ⚠️ |
| `Date` | `DateTimeImmutable` | ⚠️ | ⚠️ |
| `DateTime` | `DateTimeImmutable` | ⚠️ | ⚠️ |
| `Time` | string HH:MM:SS | ⚠️ | ⚠️ |
| `Phone` | validação E.164 | ⚠️ | ⚠️ |
| `Password` | hash + política | ⚠️ | ⚠️ |
| `Card` | agrupamento CardNumber+Cvv+ExpiryDate | ⚠️ | ⚠️ |

---

## 13. Operadores e Expressões

| PHP | PHireScript | Status |
|-----|-------------|--------|
| `+`, `-`, `*`, `/`, `%` | idem | ✅ |
| `==`, `!=`, `<=`, `>=`, `<`, `>` | idem | ✅ |
| `&&`, `\|\|` | idem | ✅ |
| `!$x` | `!x` | ✅ |
| `isset($x)` | operador interno | ✅ |
| `$a->b` (acesso a propriedade) | `a.b` | ✅ |
| `$this` | `this` | ✅ |
| `new Exception(...)` | `new Exception(...)` | ✅ |
| `(int) $x` (cast primitivo) | `Int(x)` | ✅ |
| `Email::cast($x)` (cast de SuperType) | `Email(x)` | ✅ |
| Spread `...$args` | `...args` | ⚠️ |
| Null coalescing `??` | — | 🚫 |
| Ternário `? :` | — | 🚫 |
| `instanceof` | — | 🚫 |

---

## 14. Async

| PHP | PHireScript | Status |
|-----|-------------|--------|
| Fibers / `async` (ReactPHP etc.) | `async function` / `spawn` | ❌ |

> O scanner reconhece `async` e `spawn`, mas não há contexto, resolver nem emitter.

---

## 15. Decoradores

| Equivalente PHP | PHireScript | Status |
|-----------------|-------------|--------|
| Atributo `#[Cache(...)]` | `cache { method<Duration(...)> }` | ❌ |
| Atributo `#[Schedule(...)]` | `schedule { method<Cron(...)> }` | ❌ |

> Scanner reconhece as palavras-chave, mas são esqueletos sem emissão.

---

## 16. Testes (`.pst`)

| PHPUnit | PHireScript (`.pst`) | Status |
|---------|----------------------|--------|
| `class FooTest extends TestCase` | `validate Foo { }` | ⚠️ |
| `public function testX(): void` | `test "descrição" { }` | ⚠️ |
| `setUp` / `tearDown` | `beforeEach` / `afterEach` | ⚠️ |
| `setUpBeforeClass` / `tearDownAfterClass` | `beforeAll` / `afterAll` | ⚠️ |
| `$this->markTestSkipped(...)` | `skip test "..." { }` | ⚠️ |

> Arquivos `.pst` compilam para `*Test.php` compatíveis com PHPUnit. Funciona em casos básicos, mas tem limitações conhecidas — não há cases no sandbox validando todos os cenários.

---

## 17. Pattern Matching

| PHP | PHireScript | Status |
|-----|-------------|--------|
| `match ($x) { ... }` | — | 🚫 |
| Destructuring de arrays | — | 🚫 |

---

## Resumo por área

| Área | Funcional | Parcial | Sketch | Não implementado |
|------|-----------|---------|--------|-----------------|
| Módulos e imports | 5 | 1 | — | — |
| Variáveis e literais | 9 | — | — | — |
| Tipos primitivos | 8 | — | — | 1 (union) |
| Controle de fluxo | 3 | — | 3 | 3 |
| Funções | 1 | 2 | — | — |
| OOP — declarações | 6 | — | 1 | 2 |
| OOP — membros | 18 | 3 | — | — |
| OOP — relações | 4 | — | — | — |
| Injeção de dependência | — | — | 1 | — |
| Coleções tipadas | — | 4 | — | — |
| SuperTypes | 14 | — | — | — |
| MetaTypes | 1 | 6 | — | — |
| Operadores | 9 | 1 | — | 3 |
| Async | — | — | 1 | — |
| Decoradores | — | — | 2 | — |
| Testes (.pst) | — | 1 | — | — |
| Pattern matching | — | — | — | 2 |
