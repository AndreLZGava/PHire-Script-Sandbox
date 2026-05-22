---
description: Add or extend a single skill inside an existing knowledge_base for one repo. Takes repo path plus developer intent; follows the new-skill decision framework.
argument-hint: --repo <folder-name> [--project /path] <skill goal and context — free text>
allowed-tools: Bash Read Write Edit Glob Grep
---

# Knowledge Base — New Skill (single repo)

Use this skill when a repo **already** has `knowledge_base/AGENTS.md`, `knowledge_base/index.md`, and `knowledge_base/skills/*/SKILL.md`, and you need to **add one new skill** or **extend** an existing one with minimal duplication.

## Step 1: Parse arguments

1. **`--repo <folder-name>`** (required): must match a `repos[].path` value in `{TARGET_PROJECT}/project-repos.yml`.
2. **`--project /path`** (optional): workspace root containing `project-repos.yml`; default CWD.
3. **Remaining text** in `$ARGUMENTS`: treat as developer intent — `<SKILL_GOAL>`, workflows, target modules, triggers, examples, constraints.

If `--repo` is missing → STOP and ask the user to pass `--repo <folder-name>`.

## Step 2: Resolve paths

- `TARGET_PROJECT` — from `--project` or CWD
- `REPO_ROOT` = `{TARGET_PROJECT}/{repo.path}`
- `KNOWLEDGE_BASE` = `{REPO_ROOT}/knowledge_base/`

Verify `KNOWLEDGE_BASE/AGENTS.md` and `KNOWLEDGE_BASE/index.md` exist. If not → tell user to run `/knowledge-base-generate` first.

## Step 3: Read the canonical guide

Search upward from `TARGET_PROJECT` for:

`agentic_bus/prompts/knowledge_base_new_skill_guide_prompt.md`

- **If found:** Follow it exactly for the decision framework (CREATE vs UPDATE), discovery steps, mandatory `SKILL.md` format, integration updates to `AGENTS.md` / `index.md`, quality gates, and final output sections.
- **If not found:** Follow the condensed rules below.

### Condensed rules (fallback)

1. Read every existing `knowledge_base/skills/*/SKILL.md` — build a map of triggers and scope.
2. Decide **CREATE_NEW_SKILL** vs **UPDATE_EXISTING_SKILL** using the guide’s criteria.
3. If inputs are incomplete, ask the developer the intake questions from the guide before writing files.
4. On CREATE: add `knowledge_base/skills/<new-skill-name>/SKILL.md` + `examples/*`; update `AGENTS.md` and `index.md`.
5. On UPDATE: edit the target skill only + small index/AGENTS tweaks if triggers changed.

## Step 4: Execute

- New skill names: lowercase, hyphens, no generic names (`helpers`, `misc`).
- Keep new `SKILL.md` under ~500 lines; repo-realistic examples only.
- After edits, run the guide’s quality gates (discovery, clarity, integration, concision).

## Step 5: Report

Return the sections required by `knowledge_base_new_skill_guide_prompt.md`: **Decision**, **Developer Inputs**, **Rationale**, **Changes Made**, **New or Updated Triggers**, **Validation Result**, **Follow-ups**.
