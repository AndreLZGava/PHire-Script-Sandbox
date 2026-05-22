# Glossary — PHireScript Compiler

**`Program`**
The root AST node produced by `Parser::parse()`. Contains all top-level statements of a `.ps` file: package declaration, imports, class/interface/trait/type declarations, and loose statements.

**`Token`**
Data class at `src/Compiler/Parser/Managers/Token/Token.php`. Fields: `type`, `value`, `line`, `column`, `processedBy`. Semantic helpers: `isPrimitive()`, `isSuperType()`, `isMagicMethod()`, etc.

**`Scanner`**
The lexer at `src/Compiler/Scanner.php`. Converts raw `.ps` source string into a `Token[]` array using regex patterns. Recognizes ~20 token types.

**`Validator`**
Pre-parser pass at `src/Compiler/Validator.php`. Rejects forbidden constructs and runs `ModifiersTransform` to map `+` → `protected`, `#` → `private`.

**`Parser`**
Recursive descent parser at `src/Compiler/Parser.php`. Consumes `Token[]`, drives the context stack, produces `Program` AST.

**`Context` (`AbstractContext`)**
Scope limiter in the parser. Manages what resolvers are active and when a scope closes. Every language construct has a context. Base: `src/Compiler/Parser/Ast/Context/AbstractContext.php`. Key methods: `handle()`, `canClose()`, `onClose()`.

**`Resolver` (`ContextTokenResolver`)**
Token pattern matcher. Implements `isTheCase(Token): bool` + `resolve(Token): void`. When matched, creates a `Node` and enters a new `Context`. Interface: `src/Compiler/Parser/Ast/Resolver/ContextTokenResolver.php`.

**`Node`**
Plain data holder for a parsed construct. No logic — just fields set by the `Resolver` or `Context`. Base: `src/Compiler/Parser/Ast/Nodes/Node.php`.

**`ParseContext`**
Shared runtime state passed to all contexts and resolvers: `tokenManager`, `contextManager`, `variableManager`, `symbolTableManager`, `dependencyBuilder`, `compilerContext`, `program`.

**`ContextManager`**
Manages the active context stack. Methods: `enter()`, `exit()`, `exitUntil()`, `isIn()`, `handle()`. At `src/Compiler/Parser/Managers/ContextManager.php`.

**`TokenManager`**
Token cursor. Methods: `advance()`, `walk()`, `peek()`, `sequence()`, `getNextToken()`, `matchSequence()`. At `src/Compiler/Parser/Managers/TokenManager.php`.

**`SequenceBuilder`**
Fluent API for multi-token pattern matching. Methods: `lookAhead()`, `lookBehind()`, `once()`, `then()`, `optional()`, `group()`, `or()`, `around()`, `separated()`. At `src/Compiler/Parser/Managers/Builder/SequenceBuilder.php`.

**`SymbolTable`**
Global type registry. Methods: `registerTypeDefinition()`, `getTypeDefinition()`, `enterScope()`, `exitScope()`. At `src/SymbolTable.php`.

**`SymbolTableManager`**
Parser-level access to `SymbolTable`. Auto-loads all `*Methods.php` via reflection. Resolves `getFunction()` and `getFunctionFromLastExecution()`. At `src/Compiler/Parser/Managers/SymbolTableManager.php`.

**`VariableManager`**
Tracks variables in scope. Maintains a focus pointer to the last accessed variable. At `src/Compiler/Parser/Managers/VariableManager.php`.

**`Binder`**
Orchestrator at `src/Binder.php`. Discovers all `Binder\Binder` impls via `PassDiscovery`, calls `bind(Program)` on each in order. Individual binders implement `mustBind(Node): bool` + `bind(Node, Binder): void`.

**`Checker`**
Orchestrator at `src/Checker.php`. Discovers all `Checker\Checker` impls via `PassDiscovery`, calls `check(Program)` on each. Individual checkers extend `Checker\Checker` abstract class.

**`Emitter`**
Orchestrator at `src/Emitter.php`. Builds `EmitterDispatcher` with all `NodeEmitter` impls. Entry: `emit(Program): string`.

**`NodeEmitter`**
Interface at `src/Emitter/Base/NodeEmitter.php`. Methods: `supports(object $node, EmitContext $ctx): bool`, `emit(object $node, EmitContext $ctx): string`.

**`EmitterDispatcher`**
Fast-path cache: `class-name → emitter` for O(1) lookup. Falls back to linear scan for context-dependent dispatch. At `src/Emitter/Base/EmitterDispatcher.php`.

**`EmitContext`**
Shared emission state: dev flag, use registry, type info, dependency manager, emitter reference, flags (`insideInterface`, `insideClass`, etc.). At `src/Emitter/Base/EmitContext.php`.

**`PhpFileGeneratorHandler`**
Post-emitter at `src/Processors/PhpFileGeneratorHandler.php`. Takes emitted PHP string, runs through nikic/php-parser for AST traversal + visitor transforms + pretty printing.

**`CompilerPass`**
PHP attribute `#[CompilerPass(order: int)]`. Applied to Binder and Checker implementations. Determines execution order in `PassDiscovery`.

**`PassDiscovery`**
Auto-discovers Binder and Checker implementations via reflection. Instantiates and sorts by `#[CompilerPass(order: N)]`. At `src/PassDiscovery.php`.

**`DependencyGraphBuilder`**
Builds a DAG of inter-file package dependencies. Performs topological sort to determine correct compilation order. Cached between builds. At `src/DependencyGraphBuilder.php`.

**`SuperTypes`**
Abstract base class for validated string types. Subclasses: `Email`, `Ipv4`, `Ipv6`, `Uuid`, `Color`, `Url`, `Cron`, `Duration`, etc. At `src/Runtime/Types/SuperTypes.php`.

**`MetaTypes`**
Abstract base for complex runtime types. Subclasses: `Card`, `Currency`, `Date`, `DateTime`, `Password`, `Phone`, `Time`. At `src/Runtime/Types/MetaTypes.php`.

**`*Methods.php`**
Descriptor class per type. Returns `BaseMethods[]` describing type methods (name, PHP code template, return types, params). E.g., `StringMethods`, `ArrayMethods`, `EmailMethods`. In `src/Runtime/DefaultOverrideMethods/`.

**`MagicMethods`**
Maps PHireScript magic method names to PHP `__magic` counterparts. At `src/Runtime/CustomClasses/MagicMethods.php`. E.g., `onCreate` → `__construct`, `toString` → `__toString`.

**`PhpTypeResolver`**
Resolves PHireScript type names to PHP types during emission. At `src/Emitter/Base/Type/PhpTypeResolver.php`.

**`CompileMode`**
Enum at `src/Core/CompileMode.php`. Values: `BUILD`, `TEST`, `DEBUG`, `SNAPSHOT`, `WATCH`, `CHECK`.

**`CompilerContext`**
Runtime context passed through the entire pipeline. Fields: `mode`, `inMemory`, `verbose`, `clean`, `displayInsideCompiler`, `file`, `targetWatch`. At `src/Core/CompilerContext.php`.

**`Messenger`**
CLI/web output utility. Static methods: `success()`, `error()`, `warning()`, `info()`, `muted()`. ANSI in CLI, HTML in web. At `src/Helper/Messenger.php`.

**`FatalErrorException`**
Top-level exception handler. `prettyException(Throwable)` renders errors with file context, line highlighting, column indicator. At `src/Runtime/Exceptions/FatalErrorException.php`.

**`.psc` file**
Intermediate snapshot file. Generated by SNAPSHOT mode. Shows the pre-PHP emitted string before nikic/php-parser post-processing.

**`.pst` file**
PHireScript test file. Compiled by TEST/validate mode into PHPUnit-compatible `*Test.php` files.
