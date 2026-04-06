<?php

require_once 'Container.php';

// ---------------------------------------------------------
// 1. Classes de Exemplo
// ---------------------------------------------------------

// Singleton: Conexão com Banco de Dados (Deve ser a mesma para a aplicação inteira)
class DatabaseConnection {
    public string $id;
    public function __construct() {
        $this->id = uniqid();
    }
}

// Scoped: Transação/Log de Requisição (A mesma dentro do mesmo escopo, diferente fora dele)
class RequestLogger {
    public string $id;
    public function __construct() {
        $this->id = uniqid();
    }
}

// Transient: Envio de Email (Uma nova instância para cada vez que for chamado)
class MailService {
    public string $id;
    public function __construct() {
        $this->id = uniqid();
    }
}

// Classe que recebe tudo via Injeção de Dependência no Construtor
class AppController {
    public DatabaseConnection $db;
    public RequestLogger $logger;
    public MailService $mailer;

    public function __construct(DatabaseConnection $db, RequestLogger $logger, MailService $mailer) {
        $this->db = $db;
        $this->logger = $logger;
        $this->mailer = $mailer;
    }
}

// ---------------------------------------------------------
// 2. Configurando o Container
// ---------------------------------------------------------

$container = new Container();

// Registrando os comportamentos
$container->addSingleton(DatabaseConnection::class);
$container->addScoped(RequestLogger::class);
$container->addTransient(MailService::class);

echo "=== TESTANDO INJEÇÃO DE DEPENDÊNCIAS ===\n\n";

// ---------------------------------------------------------
// 3. Executando os Testes
// ---------------------------------------------------------

// Criando dois escopos diferentes (como se fossem duas requisições separadas no Swoole)
$scope1 = $container->createScope();
$scope2 = $container->createScope();

// Resolvendo o AppController no Escopo 1 duas vezes para comparar
$controllerA_Scope1 = $scope1->get(AppController::class);
$controllerB_Scope1 = $scope1->get(AppController::class);

// Resolvendo o AppController no Escopo 2
$controllerC_Scope2 = $scope2->get(AppController::class);

echo "1. TESTE SINGLETON (DatabaseConnection):\n";
echo "Instância em A (Escopo 1): " . spl_object_id($controllerA_Scope1->db) . "\n";
echo "Instância em B (Escopo 1): " . spl_object_id($controllerB_Scope1->db) . "\n";
echo "Instância em C (Escopo 2): " . spl_object_id($controllerC_Scope2->db) . "\n";
echo "-> Conclusão: O ID do objeto é sempre o MESMO em toda a aplicação.\n\n";

echo "2. TESTE SCOPED (RequestLogger):\n";
echo "Instância em A (Escopo 1): " . spl_object_id($controllerA_Scope1->logger) . "\n";
echo "Instância em B (Escopo 1): " . spl_object_id($controllerB_Scope1->logger) . "\n";
echo "Instância em C (Escopo 2): " . spl_object_id($controllerC_Scope2->logger) . "\n";
echo "-> Conclusão: O ID é o MESMO dentro do Escopo 1, mas MUDA no Escopo 2.\n\n";

echo "3. TESTE TRANSIENT (MailService):\n";
echo "Instância em A (Escopo 1): " . spl_object_id($controllerA_Scope1->mailer) . "\n";
echo "Instância em B (Escopo 1): " . spl_object_id($controllerB_Scope1->mailer) . "\n";
echo "Instância em C (Escopo 2): " . spl_object_id($controllerC_Scope2->mailer) . "\n";
echo "-> Conclusão: O ID muda TODAS as vezes. Uma nova instância é sempre criada.\n\n";
