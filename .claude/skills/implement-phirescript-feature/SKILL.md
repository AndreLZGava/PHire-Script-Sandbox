---
name: implement-phirescript-feature
description: Orchestrates the full lifecycle of a PHireScript language feature — design evaluation, specification, planning, implementation (compiler + sandbox), and documentation update. Called for every change to phirescript/ or any part of the sandbox.
metadata:
  type: skill
---

# Skill: Implement PHireScript Feature

## Triggers

- Any change to `phirescript/` (new construct, bug fix, refactor, compiler internals)
- Any change to the sandbox that reflects a new or modified language behavior
- "I want to add X to the language", "implement feature Y", "add syntax for Z"
- Before or during speckit-specify, speckit-plan, speckit-implement for any PHireScript feature
- Whenever a speckit cycle starts on a PHireScript language feature

## When to Use

Use this skill as the **outer frame** for every PHireScript change — it wraps the speckit workflow (specify → plan → implement) with language-design evaluation and ensures documentation stays in sync after every change.

This skill does **not replace** speckit skills. It runs alongside them in this order:

```
[this skill — design gate]
  → speckit-specify
  → speckit-plan
[this skill — architecture check]
  → speckit-implement
[this skill — documentation sync]
```

---

## Phase 0 — Language Design Evaluation (BEFORE speckit-specify)

Before writing any spec, evaluate the proposed feature from two perspectives.

### Human readability

Ask:
- Is the syntax readable out loud? Would a developer unfamiliar with PHireScript understand what it does?
- Does it follow existing PHireScript conventions (PascalCase types, camelCase methods, no semicolons, `pkg`/`use` for namespacing)?
- Does it introduce new punctuation or keywords? If so, do they conflict with existing ones?
- Is the construct shorter than its PHP equivalent in a meaningful way, or just different?

### AI readability

Ask:
- Can an AI parse the intent of this construct from its syntax alone, without needing runtime context?
- Does the construct have clear delimiters (opening/closing tokens) that make scope unambiguous?
- Are there multiple interpretations of the same token sequence in different contexts? If yes — is that necessary, or can the design be simplified?
- Does the construct align with patterns already present in the language (e.g., `method?` → Bool, `method!` → Void, `as` for scope, `with` for traits)?

### Conflict check

Before approving, verify:
- Does the proposed keyword or operator already exist in PHireScript for a different purpose?
- Does the construct overlap with a Partial or Sketch feature (see `phirescript/CLAUDE.md` — Language Feature Status)?
- Does it require changes in a critical area (Scanner, Parser, SymbolTable)? If yes, assess blast radius.

### Gate rule

**Always ask before accepting any feature concept.** Even if the design seems reasonable, surface at least one of:
- A potential conflict with existing syntax
- An alternative design worth considering
- A question about edge cases

Do not proceed to speckit-specify without explicit confirmation from the developer.

---

## Phase 1 — Specification and Planning

Run the standard speckit cycle:

1. `speckit-specify` — capture the feature description
2. `speckit-clarify` — resolve underspecified areas
3. `speckit-plan` — produce design artifacts and implementation plan
4. `speckit-tasks` — generate the task list

During planning, cross-reference `phirescript/architecture.md` to:
- Identify which pipeline phases are touched (Scanner, Parser, Binder, Checker, Emitter, Processors)
- Identify which critical areas are involved (see Critical Areas section below)
- Confirm the implementation order matches the pipeline order

---

## Phase 2 — Architecture Check (BEFORE speckit-implement)

Read `phirescript/architecture.md` and verify:

1. **Token advance rule** — the only place that advances the token cursor is `phirescript/src/Compiler/Parser.php` line 64 (`$tokenManager->advance()`). Resolvers, Contexts, and Binders may use `peek()`, `lookAhead()`, `lookBehind()`, `sequence()`, and other read-only methods — but must **never** call `advance()` directly. Any design that requires a Resolver or Context to advance the cursor independently is a design smell — raise it before implementation.

2. **Trinity completeness** — every new language construct needs all three: `Context` + `Resolver` + `Node`. Partial implementations (e.g., only a Resolver with no Context) will silently fail or misparse.

3. **Binder ordering** — `TypeRegistrationBinder` (order: 10) runs first. New Binders should use order 30+. A Binder that reads type definitions must run after `TypeRegistrationBinder`.

4. **Emitter registration** — every new `NodeEmitter` must be registered in `src/Emitter.php`. Unregistered emitters are silently skipped.

5. **CompilerPass attribute** — new Binder and Checker classes must carry `#[CompilerPass(order: N)]` for `PassDiscovery` to pick them up automatically.

---

## Phase 3 — Implementation

Follow the pipeline order from `phirescript/knowledge_base/skills/add-language-feature/SKILL.md`:

1. Scanner — add token if needed
2. Parser (Node + Context + Resolver) — trinity in the correct subdirectory
3. Binder — register types, attach metadata
4. Checker — validate semantics
5. Emitter — generate PHP output
6. Sandbox case — add `samples/success/case_N/` with `.phs` file + `CaseValidation.php`

Quality gates after implementation:
```bash
# Inside phirescript/
composer quality        # rector + format + analyse
vendor/bin/phpunit      # unit tests

# At sandbox root
php bin/stretch --mode=success --tags=your-tag
```

---

## Phase 4 — Documentation Sync (AFTER speckit-implement)

After every successful implementation, update **all** of the following:

### 1. `phirescript/CLAUDE.md` — Language Feature Status

Move the feature from Sketch/Partial to the correct tier, or add it as Functional:

```markdown
### Functional — fully implemented and covered by CaseValidation in the sandbox
- **Your Feature** — brief description; sandbox cases N, M
```

### 2. `phirescript/architecture.md`

- If the feature adds a new Node/Context/Resolver/Emitter, add it to the Source Tree section
- If the feature was in the pending list (sections 1–5), move it to the Concluído table (section 6)
- If the feature introduces a new architectural concern, add it to the appropriate pending section

### 3. `knowledge_base/phirescript/` KB files

Update whichever of these is affected:
- `brief.md` — if the pipeline or overall capability changes significantly
- `glossary.md` — if the feature introduces new terminology
- `repo.md` — if the directory structure changes (new subdirectory, new key file)
- `skills/add-language-feature/SKILL.md` — if the implementation process itself changes
- `skills/compilation-pipeline/SKILL.md` — if a new phase or pass is added
- `skills/type-system/SKILL.md` — if new types or type categories are introduced

### 4. `knowledge_base/` sandbox KB files

- `knowledge_base/AGENTS.md` — update case count ("41 cases currently" → new number) if cases were added
- `knowledge_base/brief.md` — update case count if cases were added
- `knowledge_base/skills/write-phirescript/SKILL.md` — if new syntax is available to write in `.phs` files
- `knowledge_base/glossary.md` — if new PHireScript language terms appear

### 5. `prompts/architecture_review.md`

- If the feature was in the pending list, move it to "Concluído"
- If the implementation revealed new technical debt or improvement opportunities, add them to the appropriate section

---

## Critical Areas — Blast Radius

Changes in these areas affect every downstream phase. Raise with the developer before touching:

| Area | Why it's critical |
|---|---|
| `phirescript/src/Compiler/Scanner.php` | Any tokenization change affects every Resolver and every existing test |
| `phirescript/src/Compiler/Parser.php` | Token advance logic — only this file should call `$tokenManager->advance()` |
| `phirescript/src/Compiler/Parser/Ast/Context/` | Context stack rules; adding/modifying a Context can break sibling constructs |
| `phirescript/src/SymbolTable.php` | Cross-file type resolution; incorrect bindings produce silent wrong output |
| `phirescript/src/Compiler/Binder.php` | Pass ordering; wrong order causes use-before-definition in binding |

---

## Repository Context

- Compiler root: `phirescript/` (separate git repo, committed independently)
- Compiler architecture: `phirescript/architecture.md`
- Compiler CLAUDE.md: `phirescript/CLAUDE.md`
- KB for compiler internals: `knowledge_base/phirescript/`
- KB for sandbox consumption: `knowledge_base/`
- Speckit specs: `specs/<N>-<feature-name>/`
- Sandbox cases: `samples/success/case_N/`

---

## Validation Checklist

### Design gate (Phase 0)
- [ ] Feature concept confirmed by developer after design review
- [ ] No keyword/operator conflicts with existing PHireScript syntax
- [ ] No overlap with a Partial or Sketch feature that would create confusion
- [ ] Blast radius assessed for critical areas

### Implementation gate (Phase 3)
- [ ] Token advance rule respected — only `Parser.php:64` calls `advance()`
- [ ] All three parser artifacts created: Node + Context + Resolver
- [ ] Resolver registered in parent Context's resolver list
- [ ] Binder has `#[CompilerPass(order: N)]` and is discovered by PassDiscovery
- [ ] Checker has `#[CompilerPass(order: N)]`
- [ ] Emitter registered in `src/Emitter.php`
- [ ] Sandbox case passes: `php bin/stretch --mode=success --tags=your-tag`
- [ ] `composer quality` passes inside `phirescript/`
- [ ] `vendor/bin/phpunit` passes inside `phirescript/`

### Documentation gate (Phase 4)
- [ ] `phirescript/CLAUDE.md` Language Feature Status updated
- [ ] `phirescript/architecture.md` Source Tree and Concluído updated
- [ ] Affected KB files updated (brief, glossary, repo, skill docs as applicable)
- [ ] `knowledge_base/AGENTS.md` and `knowledge_base/brief.md` case count updated if new cases added
- [ ] `prompts/architecture_review.md` pending items moved to Concluído if applicable

---

## Examples

See: [examples/](examples/)
