# Method Chaining — Itens Fora do Escopo

> Itens identificados durante o design da feature method chaining (specs/003-method-chaining)
> que foram conscientemente deixados de fora da implementação atual.
> Referência para iterações futuras.

---

## 1. Resources do PHP

Streams, file handles, curl handles, database connections e outros recursos PHP (`resource` type) não têm nenhum `TypeMethod` implementado. O PHireScript ainda não suporta o tipo `resource`.

**Caminho futuro**: Criar `ResourceMethods.php` em `phirescript/src/Runtime/DefaultOverrideMethods/Types/` quando o suporte a resources for planejado. Banco de dados deve ser tratado via `external` (import de classes PHP nativas como PDO, mysqli).

---

## 2. Mapeamento completo das ~5000 funções nativas do PHP

Apenas um subconjunto das funções nativas do PHP está mapeado nos TypeMethods existentes. O mapeamento completo precisa de consulta a:
- `discovery/php_api_analysis.html`
- `discovery/php_api.html`

**Critérios para priorização futura**:
- Não mapear funções deprecated ou marcadas para remoção no PHP 9+
- Priorizar funções de string, array e tipo mais usadas
- Ignorar funções de extensões não-padrão (ldap, snmp, etc.)

---

## 3. Typed Collections com sintaxe genérica `Type<SubType>`

`List<String>`, `Map<String>`, `Queue<Int>`, `Stack<Object>` fora de declarações de método não são suportados. O parser não reconhece a sintaxe `Type<SubType>` em contexto de atribuição (`myQueue = Queue<String>`).

**Decisão de sintaxe pendente** (ver `prompts/method-chaining-analysis.md` Sugestão C):
- `Queue<String>` vs `Queue(String)` vs `Queue of String`

---

## 4. `addEnd!`, `addStart!` e métodos que usam referência PHP internamente

Métodos como `addEnd!` (`\array_push(@self, @params)`) e `addStart!` (`\array_unshift(@self, @params)`) usam passagem por referência internamente no PHP — comportamento que contradiz a regra "sem atualização de arrays por referência" do PHireScript.

**Ação futura**: Revisar os templates `phpCodeForConversion` desses métodos após finalizar o design de imutabilidade de arrays. Possível substituição por variantes que retornam um novo array ao invés de mutar o original.

---

## 5. Retorno duplo com alerta em runtime via Messenger

Métodos com `returnOfPhpExecution: ['String', 'Int']` (retorno ambíguo) deveriam idealmente gerar um alerta em runtime via `Messenger` quando o tipo real diverge do esperado na chain.

**Design definido mas não implementado**: O Emitter geraria código PHP que verifica o tipo retornado em runtime e chama `Messenger::warning()` se divergir. Complexidade de implementação elevada — deixado para iteração futura.

---

## 6. Foreach/Loop sobre resultado de chain

`each` existe em `ArrayMethods` mas o contexto `Loop` no compilador ainda é um sketch (não funcional). Chains como `myArray.filter(fn).each(fn)` não são suportadas como statement.

**Dependência**: Implementar `LoopContext` e `LoopResolver` primeiro.

---

## 7. Pattern matching sobre resultado de chain

Não implementado no compilador. Não há design para isso ainda.

---

## 8. Bibliotecas PHP customizadas (extensões via php.ini)

Funções de extensões PHP carregadas via `php.ini` (ex: imagick, redis, memcached) não têm caminho claro de integração no PHireScript.

**Possível caminho futuro**: Permitir que o desenvolvedor crie uma classe que estende `BaseMethods` ou `GeneralType` para adicionar os novos métodos diretamente no PHireScript, compilando para PHP válido. Seria uma extensão do mecanismo `external` já existente.

---

## 9. Chain sobre tipos `Null` standalone

`null.method()` — chamar um método diretamente sobre o valor `null` literal. Não faz sentido semântico na maioria dos casos mas não foi explicitamente tratado no Checker.

---

## 10. Chain em contextos não cobertos pelos testes iniciais

- Chain dentro de `return` statement: `return mystring.toUpperCase()`
- Chain dentro de `try/handle`: `result = riskyOp()?.transform()`
- Chain em parâmetro de construtor de classe: `new MyClass(mystring.length())`
- Chain sobre resultado de `external` class method além do já coberto no case_13

Esses cenários podem funcionar se os contextos relevantes já incluem `DotResolver` na lista de resolvers, mas não foram explicitamente testados.
