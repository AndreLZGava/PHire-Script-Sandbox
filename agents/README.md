# PHire-Script-Sandbox — AI Agents

Each folder here contains the definition and working memory of an AI agent that operates on this project.

## Communication Structure

```
User
  │
  ├── PHireScript Architect  ← primary interface; orchestrates all others
  │     ├── PHP Architect        ← counterweight: security, stability, output quality
  │     ├── QA                   ← validates specs before and features after
  │     ├── Developer-Compiler   ← implements transpiler and sandbox
  │     └── Developer-Extension  ← implements VS Code extension
  │
  ├── PM                    ← feature vs bug prioritization; advisory
  └── Documentador          ← documents everything; advisory
```

## Disagreements Between Architects

The PHireScript Architect drives language design and is the creative force. The PHP Architect is the counterweight — ensures security, performance, and quality of both the generated PHP and the transpiler codebase. When a real disagreement occurs, both present their arguments and the user decides.

## Agents

| Folder | Role | Talks to user? |
|---|---|---|
| `phirescript-architect/` | Language design, orchestration, AI-first tooling | Primary |
| `php-architect/` | PHP output quality, transpiler code quality | On disagreements |
| `qa/` | Spec review, edge cases, test validation | Rarely |
| `documentador/` | Feature docs, bug docs, language docs | When consulted |
| `pm/` | Backlog prioritization, feature vs bug triage | When consulted |
| `developer-compiler/` | Implementation in transpiler and sandbox | Never directly |
| `developer-extension/` | Implementation in VS Code extension | Rarely |

## Conventions

- Each agent uses its folder to record decisions, pending items, and working memory
- No agent commits — the user controls all commits
- Files in this folder are NOT git-ignored — they are part of the sandbox
