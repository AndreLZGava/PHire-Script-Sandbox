criar exception
criar decorator
implementar exception como uma forma de retorno de funções
implementar static para arrow functions que não usam this internamente
implementar named atributes para qualquer metodo
implementar os decorators cache, injection etc
implementar o recurso de gestão de injeção de dependencias
copiar classes para o codigo compilado
criar erros para outros exemplosd e checkers
melhorar o cache
implementar regra para sobrescrição de determinados tags ps em arquivos configuravel .php, .yml etc
planejar os resources do PHP
Planejar possiveis subtipos no phirescript string of type query string of type commando (shell/terminal)
validar regras de classes abastratas com propriedades abstratas
criar regras de validação para traits (não pode ter constructor)
implementar definição do tipo de variavel antes do valor dela
Adicionar a extensão dentro da pasta do sandbox como o phirescript para poder adicionar mais regras e implementar a extensão
adicionar ponto na skill de implementação ou criar uma skill que consiga ler o phirescript apra poder dar orientação da extensão da linaguagem
implementar recurso de static para methodos da classe
implementar recursos de moficadores para cada possivel tipo (classe, propriedade, methodo da classe)
planejar algum recurso de teste, modificador /gerador de codigo dentro do sandbox para garantir mais cobertura de testes ou algo nesse sentido
melhorar e dar suporte a classes validate
remover vendors do codigo assim como já estão no gitignore

--- observações técnicas registradas em 2026-06-28 ---

AccessorHandler tem nome enganoso: Processors/AccessorHandler.php transforma '.' em '->' e '+' em '.'; não tem relação com getters/setters gerados pela feature 005 — renomear ou documentar para evitar confusão futura

Getters/setters gerados não existem no AST: GetterSetterEmitter sintetiza os métodos diretamente na emissão sem criar nodes — Binder e Checker nunca os veem; se um checker futuro precisar validar esses métodos será necessária refatoração maior

Confirmar estado do case_60: a spec original tinha 'getId()' retornando 'this.id * 2' mas ReturnContext não suporta operadores aritméticos (pain point 10 em compiler-pain-points.md) — verificar se o case foi simplificado ou está bloqueado

Regra de token advance não é enforced por automação: a regra de que somente Parser.php pode chamar $tokenManager->advance() é puramente convencional, não há regra PHPStan ou outro mecanismo estático que impeça violações; avaliar criar uma regra de análise estática para isso

Features em Sketch com parser mas sem output correto (Enum, Foreach, Loop, Switch): código .ps usando essas construções pode parsear sem erro e gerar PHP inválido silenciosamente — Checker não valida, Emitter pode emitir parcial; área de risco para usuários desavisados

Backlog informal em points.md sem prioridade nem owner: formalizar os itens mais próximos de virar feature (static em métodos, named attributes, BinaryExpression em ReturnContext) como specs ou pelo menos como próximos candidatos com contexto técnico
