# PHire-Script-Sandbox

This is the integration and testing environment for **PHireScript**, a PHP transpiler. Its job is to validate that `.ps` files compile correctly and that the generated PHP behaves as expected.

## Project Structure

```
PHire-Script-Sandbox/
├── phirescript/        # PHireScript compiler (separate git repo, ignored by sandbox git)
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
