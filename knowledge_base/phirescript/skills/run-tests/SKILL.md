---
name: run-tests
description: Run the compiler's unit test suite, understand the test structure, and write new tests for Parser/Binder/Checker/Emitter
metadata:
  type: skill
---

# Skill: Run Tests

## Triggers

- "run the compiler tests", "composer test", "phpunit", "tests failing"
- "write a test for the parser", "add a test for the emitter"
- "integration test", "TranspilerTest"
- `tests/` directory

## When to Use

Use when verifying compiler correctness after a change, diagnosing a test failure, or adding test coverage for a new language feature.

## Repository Context

- Test root: `tests/` (67 files)
- PHPUnit config: `phpunit.xml`
- Autoload test namespace: `PHireScript\Tests\` → `tests/`
- Main integration test: `tests/TranspilerTest.php`
- Phase tests: `tests/Compiler/`
- Runtime tests: `tests/Runtime/`

## Key Patterns

### Running all tests

```bash
composer test
# or
vendor/bin/phpunit
```

### Running a specific file

```bash
vendor/bin/phpunit tests/Compiler/ParserTest.php
vendor/bin/phpunit tests/Compiler/Emitter/ClassEmitterTest.php
```

### Running a specific test method

```bash
vendor/bin/phpunit --filter testClassName
vendor/bin/phpunit --filter "test_compiles_interface_method"
```

### Running with coverage

```bash
vendor/bin/phpunit --coverage-html coverage-report/
# Open coverage-report/index.html in browser
```

### Test file structure

```
tests/
├── TranspilerTest.php              Integration: full .phs → .php pipeline
├── Compiler/
│   ├── BinderTest.php              Binder phase (all binders)
│   ├── CheckerTest.php             Checker phase (all checkers)
│   ├── ParserTest.php              Parser phase (AST output)
│   ├── ValidatorTest.php           Pre-parse validator
│   ├── Emitter/                    Per-emitter unit tests
│   │   ├── ClassEmitterTest.php
│   │   ├── MethodEmitterTest.php
│   │   └── ...
│   ├── Parser/                     Parser sub-component tests
│   └── Processors/                 Post-emission processor tests
├── Helper/                         TypeResolver, Messenger tests
└── Runtime/                        SuperType, MetaType runtime tests
```

### Writing a Parser test

```php
namespace PHireScript\Tests\Compiler;

use PHPUnit\Framework\TestCase;
use PHireScript\Compiler\Scanner;
use PHireScript\Compiler\Validator;
use PHireScript\Compiler\Parser;

class MyFeatureParserTest extends TestCase
{
    public function test_parses_my_construct(): void
    {
        $source = <<<'PS'
pkg PHireScript.Test

myconstruct Foo {
}
PS;
        $tokens = (new Scanner())->tokenize($source);
        $tokens = (new Validator())->validate($tokens);
        $program = (new Parser())->parse($tokens, '/test.phs');

        // Assert the AST has the expected node
        $this->assertCount(2, $program->statements); // PackageNode + MyConstructNode
        $this->assertInstanceOf(MyConstructNode::class, $program->statements[1]);
        $this->assertSame('Foo', $program->statements[1]->name);
    }
}
```

### Writing an Emitter test

```php
namespace PHireScript\Tests\Compiler\Emitter;

use PHPUnit\Framework\TestCase;
use PHireScript\Emitter\Declarations\MyConstructEmitter;
use PHireScript\Emitter\Base\EmitContext;
use PHireScript\Compiler\Parser\Ast\Nodes\Declarations\MyConstructNode;

class MyConstructEmitterTest extends TestCase
{
    public function test_emits_correct_php(): void
    {
        $node = new MyConstructNode();
        $node->name = 'Foo';

        $ctx = $this->createEmitContext();
        $emitter = new MyConstructEmitter();

        $this->assertTrue($emitter->supports($node, $ctx));
        $this->assertSame(
            'expected PHP code',
            $emitter->emit($node, $ctx)
        );
    }

    private function createEmitContext(): EmitContext
    {
        // Use the existing test helpers from the Emitter test suite
        return EmitContextFactory::make(dev: false);
    }
}
```

### Writing a full pipeline (integration) test

```php
namespace PHireScript\Tests;

use PHPUnit\Framework\TestCase;
use PHireScript\Transpiler;
use PHireScript\Core\CompileMode;
use PHireScript\Core\CompilerContext;

class MyFeatureTranspilerTest extends TestCase
{
    public function test_full_pipeline(): void
    {
        $source = <<<'PS'
pkg PHireScript.Test

class Foo {
    bar(): String {
        return 'hello'
    }
}
PS;
        $context = new CompilerContext(CompileMode::BUILD, inMemory: true);
        $transpiler = new Transpiler($context, ...);

        $php = $transpiler->compile($source, '/test.phs');

        $this->assertStringContainsString('class Foo', $php);
        $this->assertStringContainsString("return 'hello'", $php);
    }
}
```

### Quality checks (run before committing)

```bash
# Full quality suite:
composer quality

# Individual:
composer test       # PHPUnit
composer analyse    # PHPStan level 9
composer format     # PHP-CS-Fixer PSR-12 (modifies files)
composer refactor   # Rector PHP 8.2 (modifies files)
```

## Critical Rules

1. **PHPStan level 9** — all code must pass static analysis; tests included.
2. **PSR-12 style** — run `composer format` before committing.
3. **Tests are in `PHireScript\Tests\` namespace** — file paths must mirror namespace.
4. **Integration tests use `inMemory: true`** — don't write files to disk in tests.
5. **Compiler unit tests test one phase** — don't run the full pipeline in a unit test for a single class.

## Common Mistakes

- Running tests without `composer install` → autoload incomplete, immediate failure
- Writing integration tests that write to disk → flaky test ordering, leftover files
- Not running `composer format` → CI fails on style diff
- Test passes locally but fails in CI because `dump()` output corrupts assertion strings

## Validation Checklist

- [ ] `composer test` passes (no failures or errors)
- [ ] New test covers both happy path and the main error case
- [ ] Test class is in the correct namespace and directory
- [ ] `composer analyse` passes with new test file
- [ ] `composer format` applied (no style violations)

## Examples

See: [examples/](examples/)
