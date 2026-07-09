# PHire-Script-Sandbox

This is the integration and testing environment for **PHireScript**, a PHP transpiler. Its job is to validate that `.ps` files compile correctly and that the generated PHP behaves as expected.

## Project Structure

```
PHire-Script-Sandbox/
├── phirescript/        # PHireScript compiler (separate git repo, ignored by sandbox git)
├── phpscript-vscode/   # VS Code extension (separate git repo, ignored by sandbox git)
├── orchestrator/       # Test runner framework
├── samples/
│   ├── success/        # Cases expected to compile successfully
│   ├── warning/        # Cases expected to produce warnings
│   └── error/          # Cases expected to fail with errors
├── src/output/         # Compiled .php output
├── bin/stretch         # Orchestrator entry point
└── PHireScript.json    # Compiler config (source/dist paths)
```

## Relationship with PHireScript

`phirescript/` is a **separate git repository** living inside this folder for local convenience. It is listed in `.gitignore` — changes there are committed independently to its own repo (`PHPScript.git`). Do not use git subtree or any other mechanism to link the two repos.

## Relationship with the VS Code Extension

`phpscript-vscode/` is a **separate git repository** living inside this folder for convenience — same pattern as `phirescript/`. It is listed in `.gitignore` — changes there are committed independently to its own repo (`PHire-Script-Extension.git`). The sandbox acts as the shell for the extension: it provides full project context (compiler internals, language spec, sandbox cases) that AI agents need when implementing or improving the extension. Do not use git subtree or any other mechanism to link the repos.

## Running the Orchestrator

```bash
# Run all modes (success, warning, error)
php bin/stretch

# Run a specific mode
php bin/stretch --mode=success
php bin/stretch --mode=warning
php bin/stretch --mode=error

# Run multiple modes
php bin/stretch --mode=success,warning

# Filter by tag
php bin/stretch --mode=success --tags=interface,class
```

## How Cases Work

Each case is a directory inside `samples/<mode>/case_N/` containing:
- One or more `.ps` source files
- A `CaseValidation.php` that asserts the expected compilation output

A case with `CaseValidation.php` and passing assertions is the canonical indicator that a PHireScript feature is **functional**.

### CaseValidation lifecycle

```
before()
  → execute()               # assert compilation messages
  → rightAfterFirstExecution()
  → executeAgain()
  → after()
  → executeTest()           # run PHPUnit on compiled output
```

### Adding a new case

1. Create `samples/success/case_N/` (or `warning/`, `error/`)
2. Add your `.ps` files
3. Create `CaseValidation.php` extending `AbstractCaseValidation`
4. Use `assertHasMessage([...])` to assert expected compiler output
5. Run `php bin/stretch --mode=success` to validate

## Conventions

### Package naming in `.ps` files

Every `.ps` file inside `samples/success/case_N/` must declare its package as `PHireScript.SamplesN`, where **N is the exact number of the case folder**:

```
pkg PHireScript.Samples28       # file inside case_28/
use PHireScript.Samples28.{...} # import from a file in the same case
```

This ensures each case is isolated (no cross-case dependency collisions) while remaining compatible with the shared web environment, where packages are resolved globally. Using a generic suffix like `SamplesX` breaks that compatibility.

### PHireScript.json source path

The committed state of `PHireScript.json` must always have `source` pointing to a path inside `samples/`:

```json
{
  "paths": {
    "source": "samples",
    "dist": "src/compiled"
  }
}
```

`src/output` is a transient directory used internally by the orchestrator during test runs — it must never be committed as the `source`. When working on a specific case manually, change `source` locally but do not commit that change.

## Compiler Commands (via phirescript)

Run from the sandbox root — these call the phirescript compiler directly:

```bash
php phirescript/bin/build              # compile .ps → .php
php phirescript/bin/watch              # hot reload during development
php phirescript/bin/debug <file.ps>    # inspect tokens/AST
php phirescript/bin/snapshot           # generate .psc intermediate files
php phirescript/bin/validate           # compile .pst test files
```

Or via Docker (Makefile):

```bash
make build
make watch
make debug <file.ps>
make snapshot
make validate
```

## PHireScript.json

Controls which source folder the compiler reads and where it writes output:

```json
{
  "paths": {
    "source": "samples/success/case_1",
    "dist": "src/output"
  }
}
```

Change `source` to point at the case you're working on before running the compiler.

## Setup

```bash
composer install
```

PHPUnit is the only dev dependency. The compiler itself comes from `phirescript/` via the local path repository configured in `composer.json`.

<!-- SPECKIT START -->
For additional context about technologies to be used, project structure,
shell commands, and other important information, read the current plan
at `specs/009-refactors-strict-types/plan.md`
<!-- SPECKIT END -->

## Agentes de IA

O sandbox define papéis de agentes de IA que trabalham em conjunto no projeto. Cada agente tem uma pasta em `agents/` com sua definição (`AGENT.md`) e memória de trabalho.

| Agente | Papel |
|---|---|
| `agents/phirescript-architect/` | Design da linguagem, orquestração, AI-first tooling — interface principal com o usuário |
| `agents/php-architect/` | Qualidade do output PHP, código do transpilador — contrapeso de segurança e performance |
| `agents/qa/` | Validação de specs, edge cases, testes |
| `agents/documentador/` | Documentação de features, bugs, linguagem, `phirescript-doc/` |
| `agents/pm/` | Priorização de backlog, triage features vs bugs |
| `agents/developer-compiler/` | Implementação no transpilador (`phirescript/`) e sandbox |
| `agents/developer-extension/` | Implementação na extensão VS Code (`phpscript-vscode/`) |

Ver `agents/README.md` para a estrutura de comunicação entre agentes.

## PHireScript Feature Development

Whenever a change is made to `phirescript/` or to any part of this sandbox that reflects a new or modified language behavior, use the `implement-phirescript-feature` skill from the knowledge base:

```
knowledge_base/skills/implement-phirescript-feature/SKILL.md
```

This skill must be used proactively at three moments:
1. **Before specifying** any new language feature — design evaluation gate
2. **Before implementing** — architecture check (token advance rule, trinity completeness, blast radius)
3. **After implementing** — documentation sync across all affected files

**Critical rule — token advance:** Inside the PHireScript compiler, the only file that may advance the token cursor is `phirescript/src/Compiler/Parser.php` (the `$tokenManager->advance()` call). Resolvers, Contexts, Binders, and Checkers may only use read-only methods (`peek()`, `lookAhead()`, `lookBehind()`, `sequence()`, etc.). Designs that require a Resolver or Context to advance the cursor independently are architectural violations.
