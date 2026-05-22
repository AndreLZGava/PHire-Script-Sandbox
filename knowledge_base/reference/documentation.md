# External Documentation — PHire-Script-Sandbox

## PHireScript Language

- **Compiler source:** `phirescript/` (separate git repo, git-ignored in sandbox)
- **Compiler CLAUDE.md:** `phirescript/CLAUDE.md` — developer guide for the compiler
- **Architecture doc:** `phirescript/architecture.md`
- **PHireScript docs repo:** `phirescript-doc/` (documentation generation tooling)

## Sandbox

- **Project instructions:** `CLAUDE.md` at sandbox root
- **Orchestrator source:** `orchestrator/` — read the PHP files directly for authoritative behavior
- **Sample cases:** `samples/success/case_N/` — each `CaseValidation.php` is living documentation for a feature

## PHPUnit

- **Version:** ^12.x (see `composer.json`)
- **Config:** `phpunit.xml` — scans `src/compiled/` for `*Test.php`
- **Docs:** https://phpunit.de/documentation.html

## Docker

- **Compose file:** `docker-compose.yaml`
- **Dockerfile:** `Dockerfile`
- **Container name:** `phirescript_app`
- **PHP binary path inside container:** default PATH

## Spec Kit workflow

- **Spec artifacts:** `.specify/` directory
- **Plan/task files:** managed by Spec Kit skills

## Obsidian vault

- **Location:** `PhireSandbox/` — project notes and documentation in Obsidian format
