# Data Model: PHP Interop — External Class Import and Validation

**Feature**: `002-php-interop-import` | **Date**: 2026-05-30

---

## ExternalClassDescriptor

Representa a classe PHP externa inspecionada via Reflection. Criado pelo `ExternalBinder`, armazenado no `SymbolTable`, consumido pelo Checker e pelo Emitter.

```
ExternalClassDescriptor
├── className: string           # FQCN original (ex: 'DateTime')
├── alias: string               # Nome usado no PHireScript (ex: 'DateTimePhp')
├── methods: ExternalMemberInfo[]  # indexado por nome do método
├── constants: ExternalConstantInfo[]  # indexado por nome da constante
├── constructor: ExternalConstructorInfo|null
└── properties: ExternalPropertyInfo[]  # indexado por nome da propriedade
```

---

## ExternalMemberInfo

```
ExternalMemberInfo
├── name: string                # nome do método
├── isStatic: bool              # true → emite ::method(); false → emite ->method() ou (new C)->method()
├── returnType: string|string[]|null
│   # string   → tipo único (ex: 'string', 'PDOStatement')
│   # string[] → union type (ex: ['PDOStatement', 'false'])
│   # null     → sem tipo declarado (→ 'MIXED_EXTERNAL' no SymbolTable)
└── requiredParamCount: int     # mínimo de argumentos necessários
```

---

## ExternalConstantInfo

```
ExternalConstantInfo
├── name: string                # nome da constante (ex: 'ATOM')
└── value: mixed                # valor em compile-time (informativo apenas)
```

---

## ExternalConstructorInfo

```
ExternalConstructorInfo
├── isPublic: bool              # false → Checker emite erro ao usar ClassName()
├── requiredParams: ExternalParamInfo[]   # params sem default
└── optionalParams: ExternalParamInfo[]  # params com default
```

---

## ExternalParamInfo

```
ExternalParamInfo
├── name: string                # nome do parâmetro
├── type: string|null           # tipo declarado ou null
└── hasDefault: bool            # true → opcional
```

---

## ExternalPropertyInfo

```
ExternalPropertyInfo
├── name: string                # nome da propriedade
└── type: string|null           # tipo declarado ou null (sem validação nesta versão)
```

---

## SymbolTable — Extensões

### Novos métodos

```
SymbolTable
├── registerExternal(alias: string, descriptor: ExternalClassDescriptor): void
│   # Armazena em $externals[alias] = descriptor
│   # Mantido separado de $typeDefinitions para não colidir com classes PHireScript nativas
│
├── getExternal(alias: string): ExternalClassDescriptor|null
│   # Retorna null se não registrado como external
│
└── isExternalClass(name: string): bool
    # true se $name está em $externals
```

### Comportamento de colisão (FR-014)

Se `registerExternal('DateTime', ...)` for chamado e `typeDefinitions['DateTime']` já existir (classe PHireScript nativa), o Binder NÃO registra e instrui o Checker a emitir erro: *"Class 'DateTime' conflicts with a PHireScript native class. Use `external DateTime as Alias`."*

---

## Estados de Tipo no SymbolTable para Variáveis Externas

| Valor armazenado em `setType($var, ...)` | Significado | Comportamento do Checker |
|------------------------------------------|-------------|--------------------------|
| `ExternalClassDescriptor` (instância) | variável é instância de classe externa conhecida | valida membros via descriptor |
| `string[]` com múltiplos tipos externos | variável é union type external | warning se método só existe em subconjunto |
| `'MIXED_EXTERNAL'` | tipo de retorno é `mixed` ou não declarado | warning, sem validação de membros |
| `'UNKNOWN'` (padrão existente) | variável sem tipo conhecido | sem mudança no comportamento existente |

---

## Nó AST — ExternalInstantiationNode (novo)

Criado pelo Parser quando `ClassName()` é detectado e `ClassName` está registrado como external no SymbolTable (lookup durante parsing via `ParseContext`), ou alternativa: o Binder anota o `FunctionNode` com flag `isExternalInstantiation`.

**Opção preferida**: flag no Binder sobre `FunctionNode` existente — menos disruptivo ao Parser.

```
FunctionNode (existente)
└── isExternalInstantiation: bool  # novo campo — true quando ClassName() é instanciação de external
```

Quando `isExternalInstantiation = true`:
- `ExternalCallEmitter` emite `new ClassName(args)` em vez de `ClassName(args)`
- `ExternalInstantiationChecker` valida o construtor antes da emissão

---

## Fluxo de Dados — Case 15 (PDO)

```
.phs source                          AST / SymbolTable state
─────────────────────────────────   ──────────────────────────────────────────────────
external PDO                     →  ExternalNode{alias:'PDO'}
                                    Binder: ExternalClassDescriptor{
                                      alias:'PDO',
                                      methods:{
                                        'getAvailableDrivers': {isStatic:true, return:'array'},
                                        'query': {isStatic:false, return:['PDOStatement','false']},
                                        ...
                                      },
                                      constructor: {isPublic:true, requiredParams:[dsn,...]},
                                    }
                                    SymbolTable::registerExternal('PDO', descriptor)

availableDrivers = PDO.getAvailableDrivers()
                                 →  Checker: PDO registered? ✓ isStatic? ✓
                                    Emitter: PDO::getAvailableDrivers()
                                    SymbolTable::setType('availableDrivers', 'array')

query = PDO.query("SELECT ...")  →  Checker: isStatic? ✗ → método de instância em class name
                                    Checker warning: PDO constructor has required params
                                    Emitter: (new PDO())->query("SELECT ...")
                                    SymbolTable::setType('query', ['PDOStatement','false'])

user = query.fetchObject()       →  Checker: getType('query') = ['PDOStatement','false']
                                    Checker: fetchObject() exists in PDOStatement? ✓
                                    Checker warning: union type — may be 'false'
                                    Emitter: $query->fetchObject()
                                    SymbolTable::setType('user', descriptor<stdClass|false>)

user.id                          →  Emitter: $user->id (sem validação de propriedade)
```
