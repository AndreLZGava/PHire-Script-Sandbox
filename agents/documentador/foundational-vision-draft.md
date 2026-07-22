# PHireScript — Foundational Vision Draft

> Status: draft updated with founder's clarifications (2026-06-28). Six open questions resolved.
> Audience: will feed the project README, phirescript-doc/, and the language website.
> Remaining flags: string concatenation status needs compiler verification; mascot-inspired naming needs extension source review; v1.0 milestone needs formal compilation from backlog.

---

## 1. What PHire Script Is

**Name:** "PHire Script" — two words, canonical. May also appear joined as "PHireScript" in code contexts (file names, package identifiers, command output). Pronunciation: "fire script" — the `PH` is silent and reads as `F`. It is the script of fire.

PHire Script is a statically-typed, transpiled language that compiles to PHP. It is conceived as a **dev dependency**: you write code in PHire Script, and the transpiler produces idiomatic, high-performance PHP targeting the version you specify. The current target is PHP 8.2; other 8.x versions will follow.

It is not a PHP extension, a runtime, or a framework. It is a language that replaces PHP as the authoring surface while keeping PHP as the deployment target.

**Target audience:** PHP developers and AI agents that need to deliver PHP code for projects on different PHP versions, or that seek better performance and security.

---

## 2. Why It Exists

PHP has decades of accumulated inconsistency — variable sigils (`$`), statement terminators (`;`), operator fragmentation (`->`, `::`, `.`), no enforced structure per file. PHP's evolution is governed by consensus at scale: one person cannot remove `$` from PHP just because they want a cleaner language.

PHireScript solves this by being a separate language: it can enforce the conventions PHP never will, while still targeting the PHP ecosystem.

The design goal is not to replace PHP or to be antagonistic toward it. PHireScript targets only modern PHP features — no concern with deprecated functions in the initial phase. The goal is a more standardized, secure, and readable language that compiles down to excellent PHP.

---

## 3. AI-First Orientation

One of PHireScript's defining motivations is being an AI-oriented language. The syntax is designed to be lean and unambiguous — easy for language models to read, generate, and reason about — while remaining readable and expressive for humans.

This is not a bolt-on concern. It shaped early syntax decisions (operator unification, single-declaration-per-file, package system) and will continue to shape the type system design.

---

## 4. History and Timeline

| Period | What happened |
|---|---|
| December 2025 | Serious work begins. First version built with AI assistance — very close to PHP syntax, just swapping/adding components. Quickly hit the limits of that approach. |
| February 2026 | More complete transpiler, but it lost context easily. |
| ~March 2026 | ~One month of research and thinking. Transpiler rewritten around the Resolver / Context / AST pattern — the architecture still in use today. |
| Present (mid-2026) | Active feature development. Project is a study/research project, not yet usable in real PHP projects. |

The aspiration is to eventually reach the maturity needed for use in real production PHP projects.

---

## 5. Mascot — The Maned Wolf (Lobo Guará)

The mascot is the **Maned Wolf** (Lobo Guará), a Brazilian animal native to the Cerrado biome.

**Why the Maned Wolf:**
- The Cerrado burns regularly — the Maned Wolf survives and adapts.
- Reddish/orange coloring — connects visually to fire and to the "PHire" name.
- Survives across multiple Brazilian biomes — resilient, not fragile.
- Endangered but not broken — a spirit of persistence.
- Brazilian — rooted, not generic.

**Why not a phoenix:** the rebirth-through-fire concept was considered (PHire → fire → phoenix) but rejected as cliché. A real animal grounds the project in something concrete and specific.

**Why not the elephant:** PHP already uses the elephant. PHireScript needs its own identity.

The Maned Wolf's resilience and survival story map to the project's spirit: a language that absorbs the fire of a legacy ecosystem and keeps going.

**The `stretch` command:** the orchestrator entry point is named `stretch`. The concept is tensioning — you stretch something to test its limits — but also relief, like stretching after effort.

**Day / Night wolf duality — VS Code extension icons:**
The VS Code extension uses two variants of the Maned Wolf head icon:
- **Day wolf** — assigned to `.phs` script files (source code)
- **Night wolf** — assigned to `.pht` test files (test code)

The code/test duality is expressed through the same mascot in two states. This is an established part of the brand, not a proposal.

> **Flag for Documentador:** The founder believes more mascot-inspired naming exists in the language and tooling beyond what is captured here. The Documentador should review the VS Code extension source and the PHire Script compiler for other wolf-inspired identifiers, command names, or color choices, and surface them for documentation.

---

## 6. Language Design

### Inspirations
- **Primary:** TypeScript
- **Secondary:** Java, Ruby
- **Goal:** a highly expressive language

### Operator Unification — The `.` and `+` Operators

`.` is the **universal member access operator** in PHire Script. It replaces PHP's `->` (instance call) and `::` (static call):
- `$obj->method()` → `obj.method()`
- `Class::staticMethod()` → `Class.staticMethod()`
- `obj.property` → attribute access

`.` does **not** perform string concatenation. That responsibility belongs to `+`.

**String concatenation — `+`:**
String concatenation uses `+`, not `.`. It is implemented as a post-processor transformation in `AccessorHandler.php`: the regex `/(['"])\s*\+\s*|\s*\+\s*(['"])/` converts `+` adjacent to a string literal into PHP's `.` operator at emit time.

In practice:
- `"hello" + name` → compiles to `"hello" . $name` — works.
- `name + " world"` → compiles to `$name . " world"` — works.
- `varA + varB` (two variables, no string literal on either side) — the regex does not match this pattern; behavior is unconfirmed.

> **Action needed:** A sandbox test case should be written to verify whether variable-to-variable string concatenation (`varA + varB`, where both are `String` type) works or requires at least one literal operand. Until confirmed, document `varA + varB` as unsupported.

### Method Name Suffixes — Hard Compiler Rules

- Method names ending in `!` are **required to be void** — they produce a side effect and return nothing.
- Method names ending in `?` are **required to return a boolean**.

These are enforced by the compiler, not advisory conventions. A method declared with return type `void` must end in `!`; a method returning `bool` must end in `?`. This rule was introduced specifically to make PHire Script more legible to AI agents — the intent of a method call is visible in the call site itself, without inspecting the method signature.

### One Declaration Per File
Inspired by Java: each file contains exactly one class, interface, or trait. No mixing, no multiple top-level declarations. This enforces structure and makes navigation predictable.

### Package System
Also inspired by Java: every object belongs to a package/sub-package path. This compiles to a PHP namespace. Because the namespace is generated by the transpiler from the package declaration — not written manually by the developer — you can move entire folder structures without editing package declarations. The transpiler recalculates the correct PHP namespace from the file's location relative to the package root.

### No Statement Terminators / No Variable Sigils
No `;` at end of statements. No `$` prefix on variable names. These are two of the clearest PHP pain points that PHireScript simply removes.

---

## 7. Type System

PHireScript introduces a layered type system:

- **Basic types** — primitives and standard scalars
- **SuperTypes** — higher-level abstractions above basic types
- **MetaTypes** — types that describe or constrain other types

### Collections
Instead of a generic `array`, PHireScript provides specific collection types: `Stack`, `Queue`, and others. The intent is that the collection type encodes the access pattern, not just "a list of things."

### Everything Is an Object
Values are objects. You call methods on them and chain as needed.

### Subtypes (Planned, Not Yet Designed)
A planned feature is **domain-specific subtypes** — refinements of base types that carry semantic meaning. Example use case: a `SqlQuery` subtype vs a `ShellCommand` subtype, both derived from `String`. This enables:
- Method-level validation (SQL injection prevention, shell escaping enforced by the type)
- Better static analysis and flow control
- AI-legible code with explicit intent

This is acknowledged as not yet fully designed.

---

## 8. Native Testing (Planned)

PHireScript aims for a native testing approach using `.pht` files — a dedicated test extension. The design is not yet complete. The goal is that testing is a first-class concern of the language, not an afterthought.

---

## 9. PHP Resource Types (Planned)

PHireScript will need types that wrap PHP's native resource types, or subtypes for specific resource kinds. Not yet designed.

---

## 10. Project Status Summary

| Dimension | Status |
|---|---|
| Architecture (Resolver/Context/AST) | Stable |
| PHP 8.2 target | Active |
| Usable in real projects | Not yet |
| Type system (subtypes, MetaTypes) | Partially designed |
| Native testing (.pht) | Planned |
| PHP resource types | Planned |
| Domain-specific string subtypes | Concept stage |

---

## 11. v1.0 Milestone — Directional, Not Yet Formal

The project is not yet usable in a real PHP project. The founder has a directional list of what is needed before that changes:

- Function usage coverage (breadth of callable PHP functions)
- Solid validation (compiler-level and type-level)
- Enums
- PHP doc tags support
- Inject (dependency injection)
- Cache
- File loading
- Native dependency injection

This list is acknowledged as incomplete — there are more features not yet recalled. The full list needs to be compiled formally once more near-completion (NC) items are resolved.

> **Flag for PM / Architect:** A formal v1.0 milestone document should be produced by compiling the above with the full feature backlog. This draft is not that document.

---

## Open Flags (Documentador action items)

| Flag | What is needed |
|---|---|
| String concatenation `varA + varB` | Write a sandbox case to confirm whether variable-to-variable `+` concatenation works without a string literal operand. Update section 6 once verified. |
| Mascot-inspired naming in tooling | Review VS Code extension source and compiler for wolf-inspired names beyond `stretch` and the icon duality. Document findings. |
| v1.0 milestone | Coordinate with PM/Architect to compile a formal feature list from the backlog. |
| `!` / `?` enforcement details | Confirm exact compiler error messages and enforcement rules — are suffixes required on declarations, call sites, or both? |
