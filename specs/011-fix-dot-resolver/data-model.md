# Data Model: User-Defined Method Calls as Expression Operands

**Feature**: `011-fix-dot-resolver` | **Date**: 2026-07-09

---

## Entidades novas / modificadas

### `ParseContext` — campos adicionados

```php
// Nome da classe sendo parseada atualmente (null fora de corpo de classe)
public ?string $currentClassName = null;

// Métodos da classe atual: nome → tipo de retorno raw ('Int', 'String', 'Void', etc.)
public array $currentClassMethods = [];

// Registry global do arquivo: className → ['methods' => [...], 'extends' => string|null]
// Alimentado por ClassBodyResolver; navegável para herança transitiva
public array $classMethodRegistry = [];
```

**Invariantes**:
- `currentClassName` é `null` fora de um `ClassBodyContext`
- `currentClassMethods` é sempre sincronizado com `currentClassName`
- `classMethodRegistry` é acumulativo ao longo do parse de um arquivo; nunca limpo entre classes

---

### `ClassBodyResolver` — lógica adicionada

**Entrada**: token `{` que abre o body de uma classe

**Novo comportamento antes de entrar em `ClassBodyContext`**:
1. Lê `$context->node->name` (nome da classe) e `$context->node->extends->name` (pai, ou null)
2. Chama `extractMethodSignatures(TokenManager)` — lookAhead sem avançar cursor
3. Popula `$parseContext->currentClassName`, `$parseContext->currentClassMethods`, `$parseContext->classMethodRegistry[$className]`

**`extractMethodSignatures(TokenManager $tm): array<string, string>`**:
- Itera `peek($offset)` a partir de offset 0
- Conta profundidade de `{` / `}` — para ao fechar o `}` da classe (depth = 0 após o `{` inicial)
- Para cada sequência `T_HASH → T_IDENTIFIER(name) → T_OPEN_PAREN → ... → T_CLOSE_PAREN → T_COLON → T_IDENTIFIER(returnType)`:
  - Registra `$methods[$name] = $returnType`
- Retorna o mapa

**Limpeza** (ao fechar o ClassBodyContext):
- `ClassBodyContext.afterClose()` (ou `onClosingToken()` se disponível): seta `$parseContext->currentClassName = null` e `$parseContext->currentClassMethods = []`
- `classMethodRegistry` não é limpo (persiste para herança cross-class no arquivo)

---

### `FunctionCallResolver` — caminho adicionado

**`isTheCase()` — novo branch**:

```
if (focus instanceof ThisExpressionNode)
  AND token.isIdentifier()
  AND nextToken.isOpeningParenthesis()
  AND resolveFromClassHierarchy(token.value, parseContext) !== null
→ return true
```

**`resolve()` — novo branch**:

```
if (focus instanceof ThisExpressionNode AND método encontrado no classMethodRegistry):
  returnType = resolveFromClassHierarchy(token.value, parseContext)
  function = new FunctionNode(token)
  function.variableBase = focus (ThisExpressionNode)
  function.isUserDefinedMethod = true  // flag para distinguir de TypeMethods
  virtualVar = getNewVirtualVariable(token, returnType)
  parseContext.variables.setVirtualVariable(virtualVar)  // propaga tipo de retorno
  parseContext.contextManager.enter(new FunctionCallContext(function))
  context.addChild(function)
```

**`resolveFromClassHierarchy(methodName, parseContext): string|null`**:

```
className = parseContext.currentClassName
loop:
  entry = parseContext.classMethodRegistry[className] ?? null
  if entry === null → return null
  if entry['methods'][methodName] exists → return entry['methods'][methodName]
  className = entry['extends']  // sobe para o pai
  if className === null → return null
```

---

### `FunctionCallContext` — sem mudanças

O `FunctionCallContext` existente consome os parâmetros do método. Para métodos de usuário sem parâmetros (caso mais comum: `this.getBase()`), o `FunctionCallContext` fecha imediatamente ao encontrar `)`. Nenhuma mudança necessária.

---

### Emissão — sem mudanças

O `FunctionEmitter` já emite `$this->methodName()` quando `variableBase instanceof ThisExpressionNode`. Confirmado pelo funcionamento de `this.getBase()` em `ProgramContext`. A flag `isUserDefinedMethod` é usada apenas internamente no resolver para distinguir o caminho de resolução; o emitter não precisa conhecê-la.

---

## Fluxo completo após implementação

```
Input .ps:
  result = this.getBase() * this.getRate()

Tokens: 'result' '=' 'this' '.' 'getBase' '(' ')' '*' 'this' '.' 'getRate' '(' ')'

Parse em AssignmentContext → ExpressionContext:

  'result'   → VariableDeclarationNode; setVirtualVariable
  '='        → AssignmentResolver → entra ExpressionContext

  Em ExpressionContext:
  'this'     → ThisResolver → ThisExpressionNode; setVirtualVariable
  '.'        → DotResolver → mantém foco
  'getBase'  → FunctionCallResolver.isTheCase():
                 focus = ThisExpressionNode ← NOVO CAMINHO
                 resolveFromClassHierarchy('getBase') → 'Int'
                 → true ✅
               FunctionCallResolver.resolve():
                 function = FunctionNode(getBase, base=ThisExpressionNode)
                 virtualVar = NumberNode('Int')
                 → entra FunctionCallContext
  '('        → FunctionCallContext fecha imediatamente (sem params)
  ')'        → FunctionCallContext.canClose() → true → afterClose()
               → FunctionNode no children; setVirtualVariable(FunctionNode)

  '*'        → BinaryExpressionResolver → BinaryExpressionNode
  'this'     → ThisResolver → ThisExpressionNode; setVirtualVariable
  '.'        → DotResolver → mantém foco
  'getRate'  → FunctionCallResolver (mesmo caminho novo) → 'Float'
  '(' ')'    → FunctionCallContext

  EOL        → ExpressionContext.canClose() → true → afterClose()
               → AssignmentNode.right = BinaryExpressionNode

Emit:
  $result = $this->getBase() * $this->getRate();
```
