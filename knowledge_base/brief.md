# Brief — PHire-Script-Sandbox

PHire-Script-Sandbox is the **integration and regression testing environment** for PHireScript, a PHP transpiler.
Its sole job: compile `.phs` files and assert the output is correct.

- Language under test: **PHireScript** — a custom language that transpiles to PHP
- Test cases live in `samples/success/case_N/`, `samples/warning/`, `samples/error/`
- Each case has `.phs` source files + `CaseValidation.php` (lifecycle hooks + assertions)
- The orchestrator (`bin/stretch`) drives compilation and PHPUnit for all cases
- Cases are tagged with PHP attributes (`#[Tag(...)]`) for selective execution
- The compiler lives in `phirescript/` — a **separate git repo** (git-ignored here)
- The VS Code extension lives in `phpscript-vscode/` — a **separate git repo** (git-ignored here); the sandbox is its shell, providing full language and compiler context for AI-assisted development
- 60 success cases cover: classes, interfaces, traits, types, control flow, super types, magic methods, external PHP interop, method chaining (cases 42–49), getter/setter generation (cases 55–60)
- Compiled PHP lands in `src/compiled/`; PHPUnit tests scan that directory
- `PHireScript.json` controls source/dist paths and is backed up/restored per case run
- Docker + Makefile available for containerized execution; native PHP also supported
