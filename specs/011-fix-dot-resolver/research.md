# Research: User-Defined Method Calls as Expression Operands (BB-3)

**Feature**: `011-fix-dot-resolver` | **Date**: 2026-07-09

---

## Achados principais

### 1. Passes do compilador

O compilador tem 3 passes, orquestrados em `src/Compiler.php`:

| Passe | Classe | O que faz | ClassNodes disponíveis? |
|-------|--------|-----------|------------------------|
| 0 (pre-build) | `TranspilerDependencyTree` | Light parse: só `pkg`/`use`/`external` | Não — bodies ignorados |
| 1 (full parse) | `Transpiler::parseOnly()` | Parse completo incluindo bodies | Não — bind ainda não rodou |
| 1b (bind) | `Transpiler::bindProgram()` | `TypeRegistrationBinder` registra ClassNodes | Sim — mas parse já terminou |
| 2 (check+emit) | `FileManager::loadAndCompile()` | Checker + Emitter | Sim |

**Conclusão**: métodos de usuário precisam estar disponíveis no Passe 1, mas só são registrados no Passe 1b.

### 2. Por que `this.getBase()` falha

Cadeia de resolvers em `ExpressionContext` / `AssignmentContext` / `ReturnContext`:

1. `this` → `ThisResolver` → `setVirtualVariable(ThisExpressionNode)`
2. `.` → `DotResolver` → mantém foco
3. `getBase` (próximo: `(`) → `FunctionCallResolver.isTheCase()`:
   - `getFocusRawType(ThisExpressionNode)` → `null` (nenhum `getRawType()` na classe)
   - `symbolTable.from(null).getFunction('getBase')` → `null`
   - `symbolTable.getFunctionFromLastExecution('getBase')` → `null`
   - **retorna false** → `FunctionCallNotFoundResolver` → lança exceção

### 3. O que o SymbolTableManager contém

`SymbolTableManager` carrega apenas os `TypeMethods` do runtime (arquivos em `src/Runtime/DefaultOverrideMethods/Types/*.php`): `StringMethods`, `IntMethods`, `FloatMethods`, `BoolMethods`, etc. Não tem absolutamente nada sobre tipos definidos pelo usuário.

### 4. ClassBodyResolver — ponto de entrada ideal

`ClassBodyResolver.resolve()` (`src/Compiler/Parser/Ast/Resolver/Root/Class/ClassBodyResolver.php`) é chamado ao encontrar `{` após a declaração da classe. Nesse ponto:
- `$context->node` é o `ClassNode` com `name` e `extends`
- `$parseContext->tokenManager` tem todos os tokens restantes do arquivo
- Um lookAhead aqui pode extrair assinaturas de métodos sem avançar o cursor

### 5. Padrão de declaração de método nos tokens

`# methodName(params): ReturnType { ... }` tokeniza como:
- `T_HASH` (`#`)
- `T_IDENTIFIER` (nome do método)
- `T_OPEN_PAREN` (`(`)
- ... params ...
- `T_CLOSE_PAREN` (`)`)
- `T_COLON` (`:`)
- `T_IDENTIFIER` (tipo de retorno)
- `T_OPEN_CURLY` (`{`)

O lookAhead pode usar `peek(offset)` incrementando até encontrar este padrão dentro do escopo da classe (contagem de `{`/`}` para determinar profundidade).

### 6. FunctionCallResolver — estrutura de resolve()

Quando `isTheCase()` retorna `true`, `resolve()`:
1. Cria `FunctionNode` com `variableBase = focus` e `method = functionDefinition`
2. Chama `overrideVariableOnFocus()` que atualiza o foco com o tipo de retorno
3. Entra em `FunctionCallContext` para consumir os parâmetros

Para métodos de usuário, `functionDefinition` (instância de `BaseMethods`) não existirá — mas podemos criar um objeto sintético ou tratar o caso diretamente. O emitter de `FunctionNode` com `variableBase instanceof ThisExpressionNode` já gera `$this->method()` corretamente.

### 7. Herança — ClassNode.extends

`ClassNode.extends` é do tipo `ClassExtendsNode` com campo `name: string`. Para navegar a hierarquia transitivamente, basta seguir `classMethodRegistry[$className]['extends']` iterativamente.

A ordem de parse é topológica (garantida pelo `DependencyGraphBuilder`), então o pai é sempre registrado antes do filho no `classMethodRegistry`.

---

## Decisões finais

| Questão | Decisão |
|---------|---------|
| Onde registrar métodos | `ClassBodyResolver.resolve()` via lookAhead |
| Estrutura de armazenamento | `ParseContext::classMethodRegistry[className] = [methods, extends]` |
| Herança | Transitiva via loop no registry |
| Emissão de `this.userMethod()` | Reutiliza FunctionNode + FunctionCallContext existentes |
| Tipo de retorno após chamada | `getNewVirtualVariable($token, $returnType)` existente |
