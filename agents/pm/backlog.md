# PHireScript — Structured Backlog

> Compiled from: `points.md`, `compiler-pain-points.md`, `architecture_review.md`, `implementation.md`, `refactor.md`, `method-chaining-out-of-scope.md`
> Date: 2026-06-28 | Last updated: 2026-07-09 (bug MB-16 adicionado — tipos primitivos emitidos com capitalização PHireScript em vez de lowercase PHP)

---

## Next Implementation — Priority 0

**Feature 006 — Unified ExpressionContext**
Spec pronta, plano pronto, tasks pendentes (`/speckit-tasks`).
- Branch: `006-expression-context`
- Artefatos: `specs/006-expression-context/` (spec.md, plan.md, research.md, data-model.md)
- Resolve diretamente: **BB-2** (aritmética em ReturnContext) e **P1-1** (BinaryExpressionResolver em ReturnContext)
- Resolve indiretamente: duplicação em AssignmentContext e IfConditionContext (TD-19)
- Inclui: `**` no scanner, rename ComparisonExpressionResolver→BinaryExpressionResolver, negação unária `!x`/`-x`, parênteses multi-linha, novos TypeMethods (`root`, `log`, `round/floor/ceil` no Int)

---

## Milestone: v0.1 — Public Beta (Composer installable)

> **Goal**: minimum viable release for external PHP developers to install via Composer, test, stress-test, and suggest improvements. Not production-ready — this is a public beta for community feedback.

### Directional feature list for v0.1 (not exhaustive)

| Feature | Backlog ref | Status |
|---|---|---|
| Blocking bugs resolved (BB-1, BB-2, BB-3) | BB-1, BB-2, BB-3 | Blocking |
| Function usage coverage — PHP native functions on typed values | NC-1, TI-20 | Blocking |
| Solid validation — checker rules covering common errors | P2-9, P2-10, MB-5 | Blocking |
| Enum support (parser, binder, checker, emitter) | P2-11 | Blocking |
| PHP doc tags generation (`@param`, `@return`, `@throws`) | not yet in backlog | Blocking |
| Typed variable declaration — `Type name = value` | P2-32 | Blocking |
| Exception return annotation on methods | P2-33 | Blocking |
| Dependency injection / `inject {}` | P2-14 | Blocking |
| Cache decorator | P2-13 | Blocking |
| File loading / autoloading support | not yet in backlog | Blocking |
| Native DI container integration (Laravel, Symfony, custom) | P2-13, P2-14 | Blocking |
| Static method support | P1-4 | Blocking |
| Loop / foreach | P1-5 | Blocking |
| String concatenation (variable + variable) | not yet in backlog | Blocking |

> Items marked "not yet in backlog" need to be added as new backlog entries.

---

## Blocking Bugs

| # | Item | Owner | Depends on | Reason |
|---|------|-------|------------|--------|
| ~~BB-1~~ | ~~**Method parameters not registered in `variables` scope**~~ | — | — | ✅ **Fixed 2026-06-30** — `MethodDeclarationContext.processResolvers()` now iterates params and calls `addVariable()` on `enterScope`, mirroring `ArrowFunctionDeclarationContext`. `MethodEmitter` also fixed: void methods without explicit `: ReturnType` now emit `: void`. Validated via `case_66`. |
| ~~BB-2~~ | ~~**Arithmetic operators not supported in `ReturnContext`**~~ | — | — | ✅ **Already fixed** — `BinaryExpressionResolver` (formerly `ComparisonExpressionResolver`) was already wired into `ReturnContext` with full arithmetic operators. Confirmed 2026-06-30. |
| ~~BB-3~~ | ~~**`DotResolver` (Statements) `resolve()` is empty**~~ | — | — | ✅ **Parcialmente corrigido 2026-06-30** — `FunctionEmitter::overrideSelf()` agora chama `emitChainedExpression()` em vez de `wrapAsIIFE()` quando `phpCodeForConversion` é array. Inline extraction para arrays de um único `return` evita closures com `use (expressão)` inválida. Validado por `case_67` (assignment + return). Escopo restante: `IfConditionContext` é out-of-scope (requer investigação separada, documentada em `specs/007-fix-dot-resolver/plan.md §Deferred`). |

---

## Minor Bugs

| # | Item | Owner | Depends on | Reason |
|---|------|-------|------------|--------|
| ~~MB-1~~ | ~~**`ArrowFunctionEmitter` generates inverted parameter order**~~ | — | — | ✅ **Already fixed** — `ParamArgumentEmitter` already emits `type $name` in correct order. Confirmed 2026-06-30. |
| ~~MB-2~~ | ~~**`AssignmentContext` uses `children[0]` instead of `end(children)`**~~ | — | — | ✅ **Fixed 2026-06-30** — `ReturnContext::handleReturn()` changed from `$this->children[0]` to `end($this->children)`, ensuring chain expressions in `return` emit the last (correct) node. |
| ~~MB-3~~ | ~~**`FunctionNode.getRawType()` returning `'Function'` instead of the actual return type**~~ | — | — | ✅ **Fixed 2026-06-30** — `PropertyAccessNode` gained `resolvedType` field; `ThisPropertyAccessResolver` populates it from `VariableManager::getProperty()` via `onClose()` timing fix in `PropertyDeclarationContext`; `PropertyAccessNode::$type` widened from `self` to `Type` interface. `FunctionCallResolver` now correctly resolves `this.prop.method()` chains. |
| ~~MB-4~~ | ~~**`null.method()` not handled by Checker**~~ | — | — | ✅ **Fixed 2026-06-30** — `SymbolTableManager::getFunction()` now falls back to `GeneralType` methods (`defined?`, `is?`, `show!`, etc.) when the type-specific registry (`NullMethods`) doesn't exist. `null.defined?()` compiles; `null.toUpperCase()` correctly errors with improved message "Method X does not exist nor is supported on type Null". |
| ~~MB-5~~ | ~~**Features in Sketch silently emit invalid PHP**~~ | — | — | ✅ **Fixed 2026-06-30** — `EnumResolver`, `LoopResolver`, `SwitchResolver` created; registered in `ProgramContext` + 7 scope contexts. Now throws `CompileException` with clear message when `enum`/`loop`/`switch` is used. Full implementation tasks added to P2-11 and P1-5 backlog entries. |
| ~~MB-6~~ | ~~**`AccessorHandler` has a misleading name**~~ | — | — | ✅ **Fixed 2026-06-30** — renamed to `TokenTransformHandler`. PHPStan baseline entry removed. |
| ~~MB-7~~ | ~~**Commented-out ~20-line block in `ClassEmitter`**~~ | — | — | ✅ **Fixed 2026-06-30** — dead code block removed from `ClassEmitter.php`. |
| ~~MB-8~~ | ~~**`gettype()` used instead of `is_int()`**~~ | — | — | ✅ **Fixed 2026-06-30** — replaced with `\is_int()` in `FunctionEmitter.php`. |
| ~~MB-9~~ | ~~**Inconsistent string escaping in `StringMethods.php`**~~ | — | — | ✅ **Fixed 2026-06-30** — `\rtrim` uniformized to `\\rtrim` in `StringMethods.php`. |
| MB-10 | **`case_35` — Arrow function sem parâmetro: crash em `PackageContext.php:62`** — `PHP Warning: Attempt to read property "value" on null` durante compilação de `ArrowFunctions.ps`. Arrow function com zero parâmetros (`(): Void => { ... }`) aciona um caminho no `PackageContext` que tenta ler `value` de um token nulo. Descoberto 2026-06-30 via regression run. | Developer-Compiler | — | Bloqueia case_35; arrow functions sem parâmetro crasham o compilador silenciosamente |
| MB-11 | **`case_39` — Métodos de instância em classe externa (`DateTime.format`) não resolvidos** — `Method "format" does not exist nor is supported`. O `FunctionCallResolver` não encontra métodos de instâncias criadas por `external ClassName as Alias`. Mesma raiz do MB-12. Descoberto 2026-06-30. | Developer-Compiler | P2-15 | Bloqueia qualquer uso real de biblioteca PHP externa via `external` |
| MB-12 | **`case_40` — `new ExternalClass()` + chaining de métodos não funciona** — `Method "modify" does not exist nor is supported`. Ao instanciar via `DateTimePhp()`, o tipo da variável resultante não é propagado para o `SymbolTable`, então `FunctionCallResolver` não encontra os métodos subsequentes. Descoberto 2026-06-30. | Developer-Compiler | P2-15 | Extensão da mesma falha de MB-11; bloqueia case_40 |
| MB-13 | **`case_14` — `.add(value, key)` não compilado pelo compilador** — `VariablesArray.ps` usa `.add('teste', 'MDS')` com dois argumentos, mas o compilador emite `Method "add" does not exist nor is supported`. O método **existe** em `ArrayMethods.php` (linha 31); o problema está na resolução do FunctionCallResolver ao lidar com dois argumentos na chamada. `execute()` tem um `return;` defensivo mas a compilação ainda trava o run completo. Descoberto 2026-06-30; descrição corrigida 2026-07-09. | Developer-Compiler | — | Bloqueia toda a suite quando o run não usa `--from/--to` |
| MB-14 | **`case_55`/`56`/`57`/`59` — Getter/setter (`<`, `>`, `<>`) não compilam para `type` e falham em classes** — Arquivos com sintaxe de accessor inline (`< Int id`, `* > Email email`, `< > String username`) são copiados como texto plano (`[Copied]`) em vez de compilados (`✔`). O compilador não reconhece ou não processa os tokens de accessor nesses contextos. Descoberto 2026-06-30. Relacionado a `specs/005-inline-getter-setter`. | Developer-Compiler | P1-7 | Feature 005 incompleta; cases de validação falham na asserção de output |
| MB-15 | **`case_58`/`60` — Getter/setter compila mas output diverge da asserção** — `ExampleGetterSetterClass.ps` e `OverrideTest.ps` compilam com `✔` mas a `CaseValidation` lança `Exception: Expected line not found`. O PHP gerado não bate com o esperado. Pode ser namespace, método gerado com nome errado, ou ausência de método gerado. Descoberto 2026-06-30. | Developer-Compiler | P1-7 | Sub-bug da feature 005; compilação parcialmente funcional mas output incorreto |
| MB-16 | **Tipos primitivos PHireScript emitidos com capitalização incorreta para PHP** — `String` deveria sair `string`, `Bool` → `bool`, `Int` → `int`, `Float` → `float`, `Array` → `array`, `Void` → `void` no output PHP. O emitter está passando os nomes de tipo diretamente do AST PHireScript (PascalCase) sem converter para lowercase PHP. Descoberto 2026-07-09. | Developer-Compiler | — | PHP 7+ requer tipos lowercase; output atual é PHP inválido ou semanticamente diferente (classes vs primitivos) |

---

## P1 Features

| # | Item | Owner | Depends on | Reason |
|---|------|-------|------------|--------|
| P1-1 | **`BinaryExpressionResolver` in `ReturnContext`** — allow `return expr op expr` (arithmetic and comparison). Reuse existing `BinaryExpressionContext` resolvers; same pattern already used in `AssignmentContext`. (`compiler-pain-points.md` §10 proposal A) | Developer-Compiler | BB-2 | Resolves BB-2; enables any computed return value in methods |
| P1-2 | **Register method parameters in `VariableManager` scope** — in `MethodScopeResolver.resolve()`, after `enterScope()`, add each param as a `VariableDeclarationNode`. Pattern already exists in `ArrowFunctionDeclarationContext`. (`compiler-pain-points.md` §8/§10 proposal) | Developer-Compiler | BB-1 | Resolves BB-1; enables the most basic setter pattern |
| P1-3 | **`DotResolver` propagate focus in statement/assignment contexts** — implement `resolve()` body so that after `.` the focus is correctly set to the preceding node, enabling chaining outside `ProgramContext`. (`architecture_review.md` §5.1) | PHireScript Architect | — | Resolves BB-3; unblocks all real-world method chaining |
| P1-4 | **Static methods on classes** — `static function foo(): T {}` / `ClassName.foo()`. Scanner recognises `static` but emitter and checker are incomplete. (`points.md` item 19, `implementation.md` §7 class members) | Developer-Compiler | — | High-frequency OOP pattern; currently non-functional |
| P1-5 | **`loop` / `foreach` — implement `LoopContext` and `LoopEmitter`** — context skeleton exists but emitter is not implemented; blocks `loop`, `each` on arrays, and chained iteration. (`implementation.md` §4, `method-chaining-out-of-scope.md` §6) | Developer-Compiler | — | Core control flow missing; blocks iteration in any context |
| ~~P1-6~~ | ~~**Named attributes / named arguments on any method call**~~ | — | — | ✅ **Fixed 2026-07-09** — `NamedArgNode` + `NamedArgContext` + `NamedArgResolver` added; `ParamsConsumptionContext` registers `NamedArgResolver` first; `FunctionEmitter::normalizeNamedParams()` resolves by name and reorders to positional PHP output; `MethodConsumptionChecker` skips positional validation for named calls. Errors: mixed style, unknown name, missing required, duplicate. Validated via `success/case_74`, `success/case_75`, `error/case_51–54`. |
| P1-7 | **Inline getter/setter edge cases (feature 005) — verify and complete** — scanner tokens `T_ACCESSORS` are recognised but compilation has known gaps; confirm state of case_60 (blocked by BB-2). (`implementation.md` §7, `points.md` observation 3) | Developer-Compiler | BB-2 | Feature is current branch target; must reach full coverage |
| P1-8 | **Getters/setters exist only at emit time — introduce AST nodes** — `GetterSetterEmitter` synthesises methods directly without creating AST nodes; Binder and Checker are blind to them. A future checker validating getter/setter return types or override rules will require a proper AST node. (`points.md` observation 2) | PHireScript Architect | P1-7 | Architectural gap that will force a larger refactor later if not addressed now |

---

## P2 Features

| # | Item | Owner | Depends on | Reason |
|---|------|-------|------------|--------|
| P2-1 | **`switch` statement — implement `SwitchEmitter`** — context skeleton exists in parser but emitter not implemented. (`implementation.md` §4) | Developer-Compiler | — | Common control flow; sketch presence is misleading |
| P2-2 | **Union types** — `Int\|String`, `Null\|Int` in variable and parameter declarations. (`implementation.md` §3) | Developer-Compiler | — | Needed for idiomatic nullable params and multiple-type returns |
| P2-3 | **Null coalescing operator `??`** — no scanner or parser support. (`implementation.md` §13) | Developer-Compiler | — | Extremely common in PHP; notable absence |
| P2-4 | **Ternary operator `? :`** — no scanner or parser support. (`implementation.md` §13) | Developer-Compiler | — | Basic expression builder; frequently needed |
| P2-5 | **`instanceof` operator** — no support. (`implementation.md` §13) | Developer-Compiler | — | Required for type-guarded conditionals |
| P2-6 | **`final class` modifier** — no scanner or emitter support. (`implementation.md` §6) | Developer-Compiler | — | Needed for sealed class hierarchies |
| P2-7 | **`readonly class`** — no scanner or emitter support. (`implementation.md` §6) | Developer-Compiler | — | PHP 8.2 idiom for value objects |
| P2-8 | **`static` class properties** — `static x: T` partial; emitter and checker incomplete. (`points.md` item 19, `implementation.md` §7) | Developer-Compiler | P1-4 | Companion to static methods |
| P2-9 | **Abstract class with abstract properties — add checker rule** — no validation that abstract properties are implemented in subclasses. (`points.md` item 14) | Developer-Compiler | — | Silent omission of abstract property implementation |
| P2-10 | **Trait validation rules — no constructor allowed** — no checker enforcing that traits cannot declare a constructor. (`points.md` item 15) | Developer-Compiler | — | PHP language rule not enforced |
| P2-11 | **`enum` — implement parser, binder, checker, emitter** — sketch exists (scanner recognises token) but nothing else. (`implementation.md` §6) | Developer-Compiler | — | Very common PHP 8.1 construct |
| P2-12 | **Typed collections syntax `Type<SubType>` in assignment context** — `myQueue = Queue<String>` not parsed; decision on syntax also pending (`Queue<String>` vs `Queue(String)` vs `Queue of String`). (`architecture_review.md` §5.2, `method-chaining-out-of-scope.md` §3) | PHireScript Architect | — | Collections runtime exists but can't be instantiated in user code |
| P2-13 | **Decorators — `cache`, `schedule`, `inject`** — scanner recognises keywords but all three are sketches; no emitter output. (`points.md` item 6, `implementation.md` §15) | Developer-Compiler | P2-14 | Dependency injection and caching are marquee features |
| P2-14 | **Dependency injection management (`inject {}`)** — entire feature is a skeleton; `inject` keyword is scanned, but no PHP is emitted and no container integration exists for Laravel, Symfony, or `custom`. (`points.md` item 7, `implementation.md` §9) | PHireScript Architect | — | Needed before decorators make sense |
| P2-15 | **`external` class — full support beyond `use`** — currently generates correct `use` PHP but calling methods on externally imported classes is not implemented. (`implementation.md` §1) | Developer-Compiler | — | Needed for any PHP library interop beyond import |
| P2-16 | **Exception as function return type** — model exceptions as typed return values (Result pattern). (`points.md` item 3) | PHireScript Architect | P2-11 | Design-first item; needs spec before implementation |
| P2-17 | **Variable type declared separately from value** — `x: Int` on one line, `x = 5` later. (`points.md` item 16) | Developer-Compiler | — | Common pattern for deferred initialisation |
| P2-18 | **MetaType compiler support with sandbox cases** — `Currency`, `Date`, `DateTime`, `Time`, `Phone`, `Password`, `Card` runtime exists but no sandbox cases validate compilation. (`implementation.md` §12) | QA | — | Runtime is implemented but compilation path is untested |
| P2-19 | **Subtypes as domain refinements** — `String of type query`, `String of type command` (shell/terminal). (`points.md` item 13) | PHireScript Architect | — | Needs design/spec before any implementation |
| P2-20 | **`async` / `spawn` full implementation with PHP Fibers** — scanner tokens exist but no context, resolver, or emitter. (`architecture_review.md` §4.3, `implementation.md` §14) | PHireScript Architect | — | Large feature; needs design-gate before touching code |
| P2-21 | **Chain in untested contexts** — `return mystring.toUpperCase()`, chain inside `try/handle`, chain as constructor argument. May already work but not validated by any sandbox case. (`method-chaining-out-of-scope.md` §10) | QA | BB-3 | Untested paths in production compiler |
| P2-22 | **PHP Resources support** — `resource` type (streams, file handles, curl, DB connections) has no `TypeMethod` implementation. (`method-chaining-out-of-scope.md` §1) | Developer-Compiler | — | Needed for file I/O and DB access without `external` |
| P2-23 | **Spread operator `...args`** — partial status; needs full coverage and sandbox case. (`implementation.md` §13) | Developer-Compiler | — | Common variadic pattern |
| P2-24 | **`.pst` test files — full coverage with sandbox cases** — `.pst` compilation works in basic cases but no sandbox cases cover all scenarios. (`implementation.md` §16) | QA | — | Compiler's own test language is under-tested |
| P2-25 | **Copy compiled PHP classes into compiled output** — classes should be available in the compiled output without manual copying. (`points.md` item 8) | Developer-Compiler | — | Needed for standalone compiled packages |
| P2-26 | **Overrideable tags in configurable `.php`/`.yml` files** — allow certain `.ps` tags to be overridden via a config file. (`points.md` item 11) | PHireScript Architect | — | Needs spec before implementation; extensibility mechanism |
| P2-27 | **Improve and extend `validate` class support** — better support for validate-style classes. (`points.md` item 22) | Developer-Compiler | — | Partially working; needs defined scope |
| P2-28 | **`addEnd!` / `addStart!` immutability review** — methods use PHP pass-by-reference internally, contradicting PHireScript's no-mutation-of-arrays rule; should return a new array instead. (`method-chaining-out-of-scope.md` §4) | Developer-Compiler | — | Runtime design inconsistency |
| P2-29 | **Runtime type-divergence warning via `Messenger` for ambiguous return chains** — methods with `returnOfPhpExecution: ['String', 'Int']` should warn at runtime when the actual type diverges. (`method-chaining-out-of-scope.md` §5) | Developer-Compiler | BB-3 | Design defined but not implemented |
| P2-31 | **PHire Embed v1 — `@PHire{}` package reference in non-.ps files** — `PHireScript.json` gains an `embed` field listing file extensions to process (e.g., `["yml", "yaml", "php", "html"]`). When copying those files to output, the transpiler substitutes `@PHire{PHireScript.MyApp.UserService}` with the compiled PHP FQCN. v1 is limited to full `pkg` path resolution only — no logic, no expressions. v2 (`@PHireScript{}` full blocks) is a separate future feature. (`points.md` item 11) | PHireScript Architect | — | Enables Symfony/Laravel DI config, HTML templates, and PHP config files to reference PHireScript packages without knowing compiled namespaces |
| P2-32 | **Typed variable declaration — `Type name = value`** — allow declaring the type of a variable or class attribute inline before the identifier: `String example = 'hello'`, `Int count = 0`, `Email address = 'a@b.com'`. Applies to local variables inside method bodies and to class/type/immutable/trait property declarations. Currently the type is inferred or declared separately; this makes it explicit and AI-readable. Needs spec to define how this interacts with existing property declaration syntax. | PHireScript Architect | — | Explicit typing improves readability and AI comprehension; v0.1 target |
| P2-33 | **Exception return annotation on methods — `method(): ReturnType, ExceptionType`** — optional exception declaration after the return type, separated by comma: `# myMethod(): Void, Exception`. Multiple exceptions use pipe: `# myMethod(): Void, NotFoundException \| ValidationException`. The annotation is facultative — omitting it means no declared exceptions. This is a PHireScript-level contract (not PHP `@throws` directly), though it may inform PHPDoc generation. Needs spec to define: compiler enforcement (warning vs error if not declared?), emission strategy (PHPDoc `@throws`? runtime? both?). | PHireScript Architect | — | Makes error contracts explicit in the method signature; v0.1 target |
| P2-30 | **Automatic `static` inference on arrow functions** — at emit time, detect absence of `ThisExpressionNode` in the arrow function body AST; if `this` is not referenced, prefix the emitted closure with `static`. The user never writes `static` explicitly — inference is automatic. No scanner or parser changes needed. (`points.md` item 4) | Developer-Compiler | — | Correct PHP optimisation applied transparently; aligns with PHireScript "do the right thing" principle |

---

## Technical Improvements

| # | Item | Owner | Depends on | Reason |
|---|------|-------|------------|--------|
| TI-1 | **Static analysis rule for token advance violation** — the "only `Parser.php` may call `$tokenManager->advance()`" rule is purely conventional. Add a PHPStan custom rule or architecture test to enforce it statically. (`points.md` observation 4) | PHP Architect | — | Critical architectural invariant currently unenforceable |
| TI-2 | **`php -l` per-file validation → batch or replace** — `FileCompiler::compileFile()` forks a PHP process per file (~50ms each). Move to batch at end of build, conditional on `dev: true`, or replace with `nikic/php-parser` (already in pipeline). (`architecture_review.md` §1.1) | Developer-Compiler | — | Measurable performance regression at scale |
| TI-3 | **Connect `CacheManager` to Scanner and Parser** — tokens and ASTs are recomputed on every build for all files. `DependencyGraphBuilder` already knows which files changed; use that to gate re-scan/re-parse. (`architecture_review.md` §1.4) | Developer-Compiler | — | Unnecessary CPU cost on every build |
| TI-4 | **Eliminate double compilation pass** — `Compiler.compile()` does a partial parse of all files to build the dependency graph, then does a full parse again. Reuse Phase-0 ASTs in Phase-1. (`architecture_review.md` §2.1) | Developer-Compiler | TI-3 | Every file is scanned and parsed twice per build |
| TI-5 | **Scanner regex → first-character dispatch table** — currently tests ~25 patterns sequentially per position; classify by first char to reduce to 3–5 checks in the average case. (`architecture_review.md` §2.2) | Developer-Compiler | — | O(tokens × 25) → O(tokens × 3–5) |
| TI-6 | **`ClassScanner` result caching** — `listClassesExtending()` runs `token_get_all()` on every PHP file in `Runtime/` on each build; result is static between builds. Cache via `CacheManager`. (`architecture_review.md` §2.4) | Developer-Compiler | — | Repeated unnecessary I/O on unchanged files |
| TI-7 | **`FileWatcher` use `inotify` instead of polling** — current implementation recalculates `md5_file()` over every file every 0.9s. Short-term: reuse `CacheManager` hash. Long-term: `inotify_init()` / `inotify_add_watch()`. (`architecture_review.md` §2.5) | Developer-Compiler | — | CPU waste; watcher should be event-driven |
| TI-8 | **Parallelise compilation of independent files** — `DependencyGraphBuilder` already identifies independent files (no edges between them); compile them in parallel via `pcntl_fork()` or Fibers. (`architecture_review.md` §5.4) | Developer-Compiler | TI-4 | Free build-time speedup from existing graph data |
| TI-9 | **Improve `FunctionCallNotFoundResolver` error message** — current: `"This method does not exist nor is supported"`. Should include: variable name, inferred type, list of available methods for that type. (`architecture_review.md` §5.5) | Developer-Compiler | — | DX improvement; reduces debug time significantly |
| TI-10 | **`bin/inspect` improvements: `--diff`, `--context-stack`, resolver colorisation** — three follow-up improvements to the already-implemented `bin/inspect` tool. (`compiler-pain-points.md` §7 "what could be better") | Developer-Compiler | — | DX; speeds up investigation of parser/binder behaviour |
| TI-11 | **Error recovery in Parser — accumulate multiple errors** — currently aborts on first `CompileException`. Implement panic-mode sync on `\n` or `}` to collect and report all errors in one run. (`architecture_review.md` §3.2) | PHireScript Architect | — | UX quality; essential for watch mode usability |
| TI-12 | **Source maps** — no mapping between `.ps` positions and `.php` positions. Necessary for debugger integration and useful stack traces as the language grows. (`architecture_review.md` §4.4) | PHireScript Architect | — | Foundational for any debugging toolchain |
| TI-13 | **LSP (Language Server Protocol) server** — Scanner + Parser + SymbolTable already produce the required data; expose via JSON-RPC for autocomplete, go-to-definition, and real-time diagnostics. (`architecture_review.md` §5.6) | PHireScript Architect | TI-12 | Large feature; gated on source maps for accurate positions |
| TI-14 | **`PhpFileGeneratorHandler` eliminate double parse** — pipeline is `AST PHireScript → string PHP → nikic parse → AST PHP → string PHP`. Emitters should return `PhpParser\Node` directly; eliminates the round-trip. (`architecture_review.md` §3.1) | PHireScript Architect | — | High effort; high long-term benefit for emitter quality |
| TI-15 | **Dead code detection** — with dependency graph + cross-file SymbolTable, detect unused classes, unused methods, and write-only variables. Low implementation risk (post-bind analysis). (`architecture_review.md` §5.8) | Developer-Compiler | TI-4 | High developer value; safe to add incrementally |
| TI-16 | **Typed collection generics propagation** — `List<T>.map()` return type not inferred; `List<String>.map()` should know it returns `List<String>` or `String` depending on the operation. (`architecture_review.md` §4.1) | PHireScript Architect | P2-12 | Needs generics syntax first |
| TI-17 | **Sandbox test coverage tooling or generator** — some mechanism to improve sandbox case coverage (code generation, scaffolding, or a reporting tool). (`points.md` item 21) | QA | — | Vague as stated; needs scoping; listed for visibility |
| TI-18 | **Compile-time warning for PHP resource type usage** — add a Checker warning when a `resource`-returning function is called without the type being declared. (`method-chaining-out-of-scope.md` §1) | Developer-Compiler | P2-22 | Needed once resource type is added |
| TI-19 | **Runtime type-check warning for ambiguous method returns** — emit PHP that checks the actual return type at runtime and calls `Messenger::warning()` if it diverges from the declared chain type. (`method-chaining-out-of-scope.md` §5) | Developer-Compiler | BB-3 | Design done; implementation deferred |
| TI-20 | **Formalise `points.md` backlog as tracked specs** — items nearest to implementation (static methods, named attributes, `BinaryExpression` in `ReturnContext`) should become spec documents, not just a plain list. (`points.md` observation 6) | Documentador | — | Reduces AI and human context loss between sessions |

---

## Tech Debt

| # | Item | Owner | Depends on | Reason |
|---|------|-------|------------|--------|
| TD-1 | **`VariableManager` flat scope — replace with scope stack** — single array without scope hierarchy; closures, loops, and nested blocks share one namespace; will produce incorrect results as those features land. (`architecture_review.md` §2.3) | Developer-Compiler | — | Foundational correctness issue; grows harder to fix as features accumulate |
| TD-2 | **`SymbolTable` root still primitive** — registers builtins hardcoded; uses `$linePosition` as key instead of logical scope; should be simplified to a user-type registry delegating builtins to `SymbolTableManager`. (`architecture_review.md` §3.3) | PHireScript Architect | — | Accumulated technical debt from early design |
| TD-3 | **Type audit: `Node` fields typed with concrete classes** — fields like `VariableReferenceNode::$value` typed as `VariableDeclarationNode` and `FunctionNode::$type` typed as `self` caused `TypeError` in feature 003; similar violations may lurk elsewhere. Run PHPStan level 9. (`compiler-pain-points.md` §6 proposal A) | PHP Architect | — | Latent `TypeError` in untested code paths |
| TD-4 | **`VariableReferenceNode::$value` too restrictive** — should accept any `Node`, not just `VariableDeclarationNode`; chains need to set this to a `FunctionNode`. (`compiler-pain-points.md` §6 proposal B) | Developer-Compiler | TD-3 | Direct cause of `TypeError` in chain scenarios |
| TD-5 | **`FunctionNode::$type` should be `Type` interface, not `self`** — `self` prevents any assignment other than `$this`, breaking the type-slot pattern used by every other node. (`compiler-pain-points.md` §6 proposal C) | Developer-Compiler | TD-3 | Direct cause of `TypeError` in chain scenarios |
| TD-6 | **Rename `AccessorHandler` to reflect its actual behaviour** — it transforms `.` → `->` and `+` → `.`; the name implies getter/setter management which is feature 005. (`points.md` observation 1) | Developer-Compiler | — | Active source of confusion; one-line rename |
| TD-7 | **Extract `emitBodyMembers()` to base `NodeEmitter`** — `ClassEmitter`, `TraitEmitter`, and `InterfaceEmitter` duplicate the property-then-method iteration pattern. (`refactor.md` §1.1) | Developer-Compiler | — | Classic code duplication across three files |
| ~~TD-8~~ | ~~**Deduplicate `instanceof` loops in `Binder.php`**~~ — resolved by refactor: code was restructured into separate CompilerPass binders (`TypeRegistrationBinder` + `ProgramBinder`); no duplicate loops exist. **CLOSED** | — | — |
| TD-9 | **Extract `assertReturnType()` helper in `Checker.php`** — two identical `if` blocks validate `mustBeBool` / `mustBeVoid` with copy-pasted structure. (`refactor.md` §1.4) | Developer-Compiler | — | Code duplication in safety-critical checker |
| TD-10 | **Promote inline type arrays in `Binder` to class constants** — `PRIMITIVE_MAP`, `META_TYPES`, `SUPER_TYPES` are rebuilt inside a method on every call. (`refactor.md` §3.1) | Developer-Compiler | — | Unnecessary allocation; magic strings scattered inline |
| ~~TD-11~~ | ~~**Magic number `100` in `TokenManager` → named constant `DEFAULT_TOKEN_WINDOW`**~~ — `private const DEFAULT_TOKEN_WINDOW = 100` added; `getLeftTokens` and `getProcessedTokens` defaults updated. **CLOSED** (spec 009) | — | — |
| TD-12 | **Replace generic `\Exception` with domain exceptions throughout** — `Checker.php`, `DependencyGraphBuilder.php`, `PhpFileGeneratorHandler.php` all use bare `\Exception` instead of `CompileException` / `CheckerException`. (`refactor.md` §4.5, `architecture_review.md` §1.2) | Developer-Compiler | — | Breaks granular error handling; already-defined exception types exist |
| TD-13 | **`Checker::$table` injected via method instead of constructor** — `$this->table` is set inside `check()`, hiding the dependency and making the class untestable in isolation. (`refactor.md` §5.3) | PHP Architect | — | DIP violation; prevents unit testing of Checker |
| TD-14 | **`AbstractContext` template methods with empty bodies → `abstract` or throw `LogicException`** — silent no-ops when subclasses forget to override. (`refactor.md` §5.4) | PHireScript Architect | — | LSP violation; hides missing implementations |
| TD-15 | **`Checker::ensureReturnsForMethods` closed to extension** — adding `mustBeString` requires modifying the method body; should use a constraint map. (`refactor.md` §5.2) | Developer-Compiler | TD-9 | OCP violation; will be hit when new return type constraints are added |
| TD-16 | **`ContextManager` has no depth limit** — context stack can grow unbounded on deeply nested files; add a configurable limit with a descriptive `CompileException`. (`architecture_review.md` §4.2) | Developer-Compiler | — | Potential stack overflow on adversarial/erroneous input |
| TD-17 | **`TokenManager.getNextAfterFirstFoundElement()` copies 1000 tokens via `array_slice`** — replace with direct indexed iteration. (`architecture_review.md` §1.3) | Developer-Compiler | — | Unnecessary allocation on every call; simple fix |
| TD-18 | **`ContextManager.handle()` — separate `onClosingToken()` from `handle()`** — when `canClose()` returns `true`, the closing token is handled by `handle()` first, allowing resolvers to corrupt state before `exit()` runs. A dedicated `onClosingToken()` hook makes the dual role explicit. (`compiler-pain-points.md` §3 proposal B) | PHireScript Architect | — | Subtle interaction bug; same class of issue already bit `DotResolver` in feature 003 |
| TD-19 | **`ExpressionContext` — generic reusable context for RHS expressions** — `ReturnContext`, `AssignmentContext`, and `IfConditionContext` each manage their own resolver sets for binary expressions and comparisons; extract into one shared `ExpressionContext`. (`compiler-pain-points.md` §10 proposal B) | PHireScript Architect | P1-1 | Eliminates duplication across 6+ contexts; makes P1-1 easier |
| TD-20 | **`FunctionResultType` wrapper for `FunctionNode`** — `FunctionNode` implements `Type` for convenience but the semantics are ambiguous; introduce a `FunctionResultType` wrapper that holds the return type cleanly. (`compiler-pain-points.md` §5 proposal B) | PHireScript Architect | TD-5 | Semantic clarity; reduces risk of wrong-type bugs in chain resolution |
| TD-21 | **Remove `vendors` from git tracking** — already in `.gitignore` but may still be tracked. (`points.md` item 23) | Developer-Compiler | — | Standard hygiene |

---

## Needs Clarification

| # | Item | Why unclear | Owner |
|---|------|-------------|-------|
| NC-1 | **PHP native function mapping (~5000 functions)** — criteria exist (skip deprecated, prioritise string/array, ignore non-standard extensions) but no prioritised list of next functions to add exists. How many and which functions are the next milestone? (`method-chaining-out-of-scope.md` §2) | No acceptance criterion; scope is open-ended | PHireScript Architect |
| NC-3 — resolved → P2-31 | see P2 Features |  |  |
| NC-4 | **Subtype system: `String of type query`** — `points.md` item 13 proposes `String of type query` and `String of type command` as subtypes. There is no design for the syntax, how this maps to PHP, or how the type checker would use it. | Concept only; no design | PHireScript Architect |
| NC-5 | **Add PHireScript extension inside the sandbox** — `points.md` item 17: "add extension inside the sandbox folder like phirescript to add more rules and implement the extension". It is unclear whether this means (a) a Composer extension package, (b) a sandbox-specific plugin folder, or (c) something else entirely. | Mechanism not specified | PHP Architect |
| NC-6 | **Skill that reads phirescript to guide extension** — `points.md` item 18: "add a point to the implementation skill or create a new skill that can read the phirescript to give guidance on language extension". The deliverable (prompt, tool, skill file) and the trigger conditions are not defined. | Tooling requirement without spec | Documentador |
| NC-7 | **Access modifiers for all possible types** — `points.md` item 20: "implement modifiers for each possible type (class, property, class method)". PHireScript already has several modifiers; it is unclear which specific modifiers are missing and for which constructs. | Too broad; needs an inventory of what is missing vs present | Developer-Compiler |
| NC-8 | **Pattern matching — design needed** — `implementation.md` §17 / `method-chaining-out-of-scope.md` §7: no design exists for syntax or semantics. Both files note it as not implemented with no proposed approach. | No spec | PHireScript Architect |
| NC-9 | **`readonly x: T` on class properties — partial status** — `implementation.md` §7 marks `readonly` as `⚠️ partial`. What exactly is broken or missing is not documented. | Needs investigation before a fix can be scoped | QA |
| NC-10 | **PHP Resources — prioritisation** — `method-chaining-out-of-scope.md` §1 notes resources should be via `external` for DB, but streams/file handles are left open. Is a `ResourceMethods.php` actually planned, or is the intent to always use `external`? | Decision not made | PHireScript Architect |
| NC-11 | **`DotResolver` interaction bug fix scope** — `compiler-pain-points.md` §3 proposes both a targeted fix (already applied in feature 003) and a structural `onClosingToken()` refactor (TD-18). It is unclear whether the targeted fix is considered sufficient for now or whether the structural refactor is expected before new features use closing tokens. | No decision recorded | PHireScript Architect |
| ~~NC-12~~ | ~~**case_60 state**~~ | ✅ **Resolvido 2026-07-09** — `case_60` não depende de BB-2; `CaseValidation` só verifica compilação e unicidade de `getId()`, sem aritmética. O estado real do case já está coberto por MB-15 (getter/setter compila mas output diverge da asserção). Item encerrado. |  |

---

**Total items: 0 Blocking Bugs · 16 Minor Bugs (7 open, 9 fixed) · 8 P1 Features (1 fixed) · 33 P2 Features · 20 Technical Improvements · 21 Tech Debt · 10 Needs Clarification**
