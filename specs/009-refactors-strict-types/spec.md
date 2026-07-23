# Feature Spec: Compiler Refactors + strict_types Output

**Spec**: 009 | **Date**: 2026-07-09

## Overview

Two targeted compiler improvements bundled into a single delivery:

1. **TD-11** — Replace magic number `100` in `TokenManager` with named constant `DEFAULT_TOKEN_WINDOW`.
2. **strict_types output** — All PHP files emitted by PHireScript must include `declare(strict_types=1);` immediately after `<?php`.

## User Stories

### US1 (P1): TD-11 — Named constant in TokenManager

**As a** compiler developer,
**I want** the token window size to be expressed as a named constant,
**so that** the intent is self-documenting and future tuning is a one-line change.

**Acceptance criteria**:
- `TokenManager` declares `private const DEFAULT_TOKEN_WINDOW = 100`
- `getLeftTokens()` and `getProcessedTokens()` use `self::DEFAULT_TOKEN_WINDOW` as default
- `getNextAfterFirstFoundElement()` still uses `1000` (intentionally different window)
- All existing tests pass

### US2 (P1): strict_types in generated PHP

**As a** PHP developer using PHireScript output,
**I want** every compiled `.php` file to declare `strict_types=1`,
**so that** the PHP runtime enforces the type guarantees PHireScript already encodes in its type system.

**Acceptance criteria**:
- `ProgramEmitter` emits `declare(strict_types=1);` on line 3 of every generated PHP file (after `<?php` + blank line)
- All `.phc` snapshot files are regenerated to reflect the new header
- All sandbox cases still pass `php bin/stretch --mode=success`
- Any sandbox case that surfaces a latent type mismatch must be fixed (not suppressed)
- A new sandbox case (or updated existing case) validates the output header

## Out of Scope

- TD-8: Closed as resolved-by-refactor (binder restructuring already eliminated the duplicate loops)
- Any changes to the VS Code extension
- Any changes to the test runner / orchestrator
