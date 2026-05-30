# Contract: External Class Pipeline

**Feature**: `002-php-interop-import` | **Date**: 2026-05-30

---

## Transformações de Compilação (PHireScript → PHP)

### Declaração

| PHireScript | PHP gerado |
|-------------|-----------|
| `external DateTime as DateTimePhp` | `use DateTime as DateTimePhp;` |
| `external PDO` | `use PDO;` |
| `external Symfony\Component\HttpFoundation\Request` | `use Symfony\Component\HttpFoundation\Request;` |

### Chamadas de método estático (class name direto)

| PHireScript | PHP gerado | Condição |
|-------------|-----------|----------|
| `DateTimePhp.createFromFormat('d/m/Y', '25/12/2023')` | `DateTimePhp::createFromFormat('d/m/Y', '25/12/2023')` | `createFromFormat` é estático |
| `PDO.getAvailableDrivers()` | `PDO::getAvailableDrivers()` | `getAvailableDrivers` é estático |

### Chamadas de método de instância (class name direto)

| PHireScript | PHP gerado | Condição |
|-------------|-----------|----------|
| `PDO.query("SELECT 1")` | `(new PDO())->query("SELECT 1")` | `query` é de instância; construtor tem args obrigatórios → warning |

### Acesso a constantes

| PHireScript | PHP gerado |
|-------------|-----------|
| `DateTimePhp.ATOM` | `DateTimePhp::ATOM` |
| `PDO.FETCH_OBJ` | `PDO::FETCH_OBJ` |

### Instanciação

| PHireScript | PHP gerado |
|-------------|-----------|
| `date = DateTimePhp()` | `$date = new DateTimePhp();` |
| `date = DateTimePhp('2023-12-25')` | `$date = new DateTimePhp('2023-12-25');` |

### Chamadas em variáveis de tipo externo inferido

| PHireScript | PHP gerado | Condição |
|-------------|-----------|----------|
| `date.modify('+3 days')` onde `date: DateTime` | `$date->modify('+3 days')` | `modify` é de instância |
| `date.format('Y')` | `$date->format('Y')` | `format` é de instância |
| `user.id` onde `user: stdClass\|false` | `$user->id` | propriedade — sem validação |

---

## Erros e Warnings de Compilação

### Erros (bloqueiam compilação)

| Cenário | Mensagem |
|---------|---------|
| Classe não disponível no autoloader | `Cannot load external class 'ClassName': not found in autoloader. Check your Composer dependencies.` |
| Método não existe na classe | `Method 'methodName' does not exist in external class 'ClassName'.` |
| Membro existe mas não é público | `Member 'name' in external class 'ClassName' is not public.` |
| Construtor não-público e ClassName() usado | `Cannot instantiate 'ClassName': constructor is not public.` |
| Construtor tem args obrigatórios e ClassName() usado sem args | `Cannot instantiate 'ClassName': constructor requires parameters: dsn, username, password.` |
| Constante não existe na classe | `Constant 'NAME' does not exist in external class 'ClassName'.` |
| Conflito de nome sem alias | `External class 'DateTime' conflicts with a PHireScript native class. Use 'external DateTime as Alias'.` |

### Warnings (compilação prossegue)

| Cenário | Mensagem |
|---------|---------|
| Tipo de retorno é `mixed` ou não declarado | `Return type of 'ClassName::method()' is mixed or undeclared — subsequent calls on the result will not be validated.` |
| Union type e método existe apenas em parte dos tipos | `Return type of 'ClassName::method()' is 'TypeA\|TypeB' — 'nextMethod()' may not exist on all types at runtime.` |

---

## Invariantes

1. `ExternalEmitter` não é modificado — continua emitindo apenas `use` statements.
2. `PropertyAccessEmitter` decide static/instance/const consultando o SymbolTable, nunca por heurística sintática.
3. `ExternalClassDescriptor` é imutável após criado pelo Binder.
4. A inspeção Reflection de uma classe ocorre no máximo uma vez por sessão de compilação (cache no SymbolTable).
5. Classes PHireScript nativas sempre têm precedência — `isExternalClass()` retorna false se o nome está em `typeDefinitions`.
