---
description: Generate or refresh knowledge_base/ in each repo listed in project-repos.yml, following the agnostic KB specification. Optional single-repo scope.
argument-hint: [--project /path/to/workspace] [--repo <folder-name>] [--force]
allowed-tools: Bash Read Write Edit Glob Grep
---

# Knowledge Base Generate (multi-repo)

Build a **repository-specific** `knowledge_base/` tree under each code repo declared in `{TARGET_PROJECT}/project-repos.yml`, using the same structure and quality rules as the Agentic Bus canonical prompt.

## Step 1: Resolve `TARGET_PROJECT`

1. **`--project /path`** in `$ARGUMENTS` → that path
2. Else CWD if `project-repos.yml` exists there
3. Else STOP — user must pass `--project`

## Step 2: Load `project-repos.yml`

Read `{TARGET_PROJECT}/project-repos.yml`. Parse `repos[].path` for every repository that should receive a knowledge base.

- **`--repo <folder-name>`** (optional): process only the entry whose `path` matches `<folder-name>` (exact match against `repos[].path`).
- **`--force`** (optional): regenerate even when `knowledge_base/` already exists (default: skip repo if `knowledge_base/AGENTS.md` exists unless `--force`).

## Step 3: Locate the canonical specification (read once)

Search upward from `TARGET_PROJECT` for a directory that contains:

`agentic_bus/prompts/knowledge_base_agnostic_prompt.md`

Typical layout: workspace root contains both `agentic_bus/prompts/...` and `agentic_bus/workspace/force/...`.

- **If found:** Read that file and follow it **exactly** for structure, skill skeleton, quality gates, and writing style.
- **If not found:** Follow the **Required Output** structure embedded below (same as the prompt file).

### Required output structure (fallback if prompt file missing)

Under each repo root `{TARGET_PROJECT}/{repo.path}/`:

```text
knowledge_base/
├── AGENTS.md
├── index.md
├── brief.md
├── repo.md
├── glossary.md
├── skills/
│   ├── <skill-name>/
│   │   ├── SKILL.md
│   │   └── examples/
│   └── ...
├── reference/
│   ├── documentation.md
│   ├── codeStands.md
│   └── dependency_graph.toon   (placeholder if not generated)
└── tech_done/
    └── tech_lead_done.md
```

Each `skills/<name>/SKILL.md` must use the frontmatter + sections from the canonical prompt (triggers, When to Use, Repository Context, Key Patterns, Critical Rules, Common Mistakes, Validation Checklist, Examples).

## Step 4: Generate per repo

For each selected `repo.path`:

1. `REPO_ROOT="{TARGET_PROJECT}/{repo.path}"` — verify directory exists.
2. Inspect the **actual** codebase under `REPO_ROOT` (languages, build, tests, layout). Do **not** assume a stack; infer from files.
3. Create or update `knowledge_base/` per spec. Prefer concise, trigger-based skills (6–12 skills) grounded in real paths and commands from this repo.
4. Write `tech_done/tech_lead_done.md` with: what was generated, key findings, assumptions, suggested next improvements.

## Step 5: Report

For each repo:

```
REPO: {repo.path}
STATUS: created | updated | skipped (reason)
FILES: <count> top-level + skills
```

## Rules

- **Agnostic:** infer stack from repo; state assumptions explicitly.
- **No duplicate** generic skills — every skill must map to patterns you observed in that repo.
- **Paths** in docs must be relative to that repo root or clearly labeled absolute under `REPO_ROOT`.
