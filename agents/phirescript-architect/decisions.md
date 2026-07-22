# Design Decisions

## Foundational Vision & Design Principles (2026-06-28)

### PHireScript Identity

PHireScript is "TypeScript for PHP" — a transpiled language that brings modern, standardized syntax to the PHP ecosystem while eliminating PHP's accumulated bad habits. It compiles `.phs` source files into optimized PHP (currently targeting PHP 8.2+).

**Status**: Research and study project. Not production-ready. Decisions must be made with long-term coherence in mind, not short-term delivery pressure.

**Mascot**: The Maned Wolf (Lobo Guará) — Brazilian, resilient, reddish/orange. Lives through wildfires and emerges intact. Maps directly to PHireScript's fire/rebirth spirit: burning away PHP's legacy and emerging cleaner.

---

### Core Design Principles

**1. AI-first, human-friendly**
Every syntax decision must be natural for an AI agent to read and for a human to understand. Lean, unambiguous syntax. No ceremonial noise. When two designs are equivalent in power, pick the one that reads better aloud.

**2. Dev dependency model**
PHireScript is a build-time tool. It compiles `.phs` → optimized PHP for the target runtime. The PHP output is what ships. PHireScript source never runs directly.

**3. Only modern PHP — no backward compatibility debt**
No support for deprecated PHP functions or patterns. The compiler targets PHP 8.2+ features. This is a feature, not a limitation: the output can be assumed to be clean, secure, and idiomatic modern PHP.

**4. No PHP bad habits in syntax**
- No semicolons at end of statements
- No `$` prefix on variable names
- No `->` vs `::` distinction exposed to the user — the `.` operator unifies instance access, static access, and string concatenation
- The language should feel like a fresh start, not a thin wrapper

**5. One declaration per file (Java model)**
Each `.phs` file declares exactly one class, interface, trait, or enum. Enforced by the compiler. This constraint enables predictable tooling, clean imports, and a 1:1 mapping between package paths and declarations.

**6. Package system decoupled from the file system (Java model)**
The `pkg` declaration in a `.phs` file defines the package — not the directory path. Moving or renaming files does not break package resolution. The compiler resolves packages by scanning and indexing `pkg` declarations, not by directory convention.

**7. Expressive naming conventions**
- `!` suffix = void method (no return value expected)
- `?` suffix = boolean method (returns true/false)
These are compiler-enforced semantic signals, not just style. They reduce the need for return-type annotations in common cases and make intent immediately visible.

**8. Everything is an object — full method chaining**
Primitive values expose methods. Return values are always chainable. The language must make it natural to write `"hello".toUpper().trim()` or `42.toString()` without ceremony.

**9. SuperTypes and MetaTypes instead of raw arrays**
PHireScript replaces PHP's generic `array` with purpose-built collection types:
- `Stack`, `Queue`, and other specific collection structures
- `SuperTypes` for extended primitive behavior
- `MetaTypes` for type-level abstractions
Raw arrays are a PHP implementation detail — PHireScript code should never need to think in arrays.

**10. Subtypes for domain strings (planned)**
Specific string subtypes for semantically distinct string values — e.g., SQL queries, shell commands — to enable static validation and prevent injection-class bugs. Design not yet started; reserved for a future spec cycle.

**11. SOLID, KISS, DRY applied to both the language and the transpiler**
A design that requires complicated transpiler mechanics to implement is a design smell. If implementing a feature requires violating the token advance rule or creating a tangled resolver, the feature design should be revisited first.

---

### Constraints That Must Not Be Violated

- The `.` operator is the single access operator — no design should re-introduce `->` or `::` as visible syntax
- `$` prefix is permanently gone — no edge case justifies it
- Semicolons are permanently gone — statement boundaries are inferred
- One declaration per file is a hard constraint — the compiler enforces it, features must not work around it
- Package paths are logical, not filesystem-derived — no feature should rely on directory structure for resolution

---

## 2026-06-28

### Embedded PHireScript in non-.phs files (NC-3) — v1 and v2 design

**Feature name**: PHire Embed

**v1 — `@PHire{}`** (package reference only)
- Syntax: `@PHire{PHireScript.MyApp.UserService}`
- Supported in: any file extension listed in `PHireScript.json` under a new `embed` field (e.g., `"embed": ["yml", "yaml", "php", "html"]`)
- The transpilador resolves the full `pkg` path (exactly as declared in the `.phs` file) to its compiled PHP FQCN and performs a text substitution when copying the file to output
- Limitation: only package path resolution — no logic, no expressions, no declarations
- The user writes the full `pkg` path as it appears in the `.phs` file (e.g., `PHireScript.MyApp.UserService`, not a shortened form)

**v2 — `@PHireScript{}`** (full PHireScript block)
- Syntax: `@PHireScript{ ... any valid PHireScript ... }`
- The tag change signals to both the transpiler and the reader that this is a full code block, not just a reference
- Scope: to be designed in a future spec; not in scope for v1

**PHireScript.json config (v1)**:
```json
{
  "embed": ["yml", "yaml", "php", "html"]
}
```

**Rationale**: `@PHire{}` vs `@PHireScript{}` as a versioning signal is intentional — the tag name communicates the capability level. Consistent with how other ecosystems distinguish simple interpolation from full template blocks.

**Owner**: PHireScript Architect (spec) → Developer-Compiler (implementation)
**Priority**: reclassified from NC-3 to P2 feature (after v1 spec is written)

### Typed variable declaration — `Type name = value` (P2-32)

**Decision**: Allow explicit type annotation on variable and attribute declarations using the form `Type name = value`. Applies to local variables inside method bodies and to class/type/immutable/trait properties.

**Examples**:
```
String example = 'this is an example'
Int count = 0
Email address = 'user@example.com'
```

**Why**: Makes the type contract explicit and immediately readable by both humans and AI agents without requiring type inference. Aligns with the "AI first, human friendly" principle — a model reading the code sees the type before the identifier.

**Open questions for spec**:
- How does this interact with the existing property declaration syntax (`String name` without `=`)?
- Is `Type name` (no value) also valid, or is the `= value` always required?
- Does this require scanner/parser changes, or can it reuse the existing `PropertyResolver` / `VariableDeclarationResolver` with an extended pattern?

**Priority**: P2, v0.1 target
**Owner**: PHireScript Architect (spec) → Developer-Compiler (implementation)

---

### Exception return annotation on methods (P2-33)

**Decision**: Allow an optional exception declaration after the return type in method signatures, separated by a comma. Multiple exceptions use `|`.

**Syntax**:
```
# myMethod(): Void, Exception { ... }
# myMethod(): Void, NotFoundException | ValidationException { ... }
# myMethod(): String { ... }   // no exception declared — still valid
```

**Rules**:
- The exception annotation is entirely optional — omitting it is valid
- When declared, it is a PHireScript-level contract stating the method may throw those types
- Multiple exceptions: `ExceptionA | ExceptionB`
- Must not conflict with the `?` boolean return convention or the `!` void convention

**Open questions for spec**:
- Is the compiler enforcement a warning, an error, or informational only? (i.e., if a method throws `NotFoundException` but doesn't declare it, does the compiler complain?)
- Emission strategy: does this generate PHPDoc `@throws`? Runtime annotations? Both?
- Does this feed the PHPDoc generation feature (not yet in backlog)?
- How does this interact with the `Exception as return type` feature (P2-16 — Result pattern)?

**Priority**: P2, v0.1 target
**Owner**: PHireScript Architect (spec) → Developer-Compiler (implementation)

---

### Static inference on arrow functions (NC-2)

**Decision**: The compiler automatically infers `static` on arrow functions that do not reference `this` internally. The user never writes `static` explicitly — the compiler detects the absence of `this` usage in the function body and emits `static function(...) {}` in the generated PHP.

**Rationale**: Consistent with the PHireScript "AI first, human friendly" principle — the language should do the right thing without requiring the developer to remember to annotate it. PHP itself benefits from `static` closures (avoids binding `$this`, slightly faster), and the compiler has full visibility into the AST to make this inference safely.

**Implementation note**: The check should happen in the Emitter (or a new Checker pass) — scan the arrow function body AST for any `ThisExpressionNode`; if none found, prefix the emitted closure with `static`. No scanner or parser changes needed.

**Owner**: Developer-Compiler
**Priority**: reclassified from NC-2 to P2 feature

---

### BB-3 fix strategy — `onClosingToken()` structural refactor (2026-06-30)

**Decision**: BB-3 (DotResolver.resolve() vazio — method chaining fora de ProgramContext) será corrigido via **Opção B** — refactor estrutural `onClosingToken()` (TD-18), não via fix targeted.

**Rationale**: O fix targeted deixa o bug de classe ativo — quando `canClose()` retorna true, o token ainda passa por `handle()` primeiro, permitindo que resolvers corrompam estado antes do `exit()` rodar. Esse é exatamente o bug que mordeu o DotResolver na feature 003. A Opção A resolveria o sintoma sem eliminar a causa.

**Scope**: Separar `handle()` em dois papéis: `handle()` para tokens normais e `onClosingToken()` para o token que fecha o contexto. Toca `AbstractContext`, `ContextManager`, e todos os contextos com `canClose()`.

**Pre-condition**: Requer spec dedicada antes de implementar (blast radius alto — todos os contextos existentes).

**Owner**: PHireScript Architect (spec) → Developer-Compiler (implementation)
**Priority**: P1 — desbloqueia BB-3 e TD-18 de uma vez
