<?php

require_once 'Container.php';

// ---------------------------------------------------------
// 1. Example Classes
// ---------------------------------------------------------

// Singleton: Database Connection (must be the same across the entire application)
class DatabaseConnection {
    public string $id;
    public function __construct() {
        $this->id = uniqid();
    }
}

// Scoped: Request Transaction/Log (same within the same scope, different outside it)
class RequestLogger {
    public string $id;
    public function __construct() {
        $this->id = uniqid();
    }
}

// Transient: Email Sending (a new instance every time it is called)
class MailService {
    public string $id;
    public function __construct() {
        $this->id = uniqid();
    }
}

// Class that receives everything via Constructor Dependency Injection
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
// 2. Setting up the Container
// ---------------------------------------------------------

$container = new Container();

// Registering the behaviors
$container->addSingleton(DatabaseConnection::class);
$container->addScoped(RequestLogger::class);
$container->addTransient(MailService::class);

echo "=== TESTING DEPENDENCY INJECTION ===\n\n";

// ---------------------------------------------------------
// 3. Running the Tests
// ---------------------------------------------------------

// Creating two different scopes (as if they were two separate requests in Swoole)
$scope1 = $container->createScope();
$scope2 = $container->createScope();

// Resolving AppController in Scope 1 twice for comparison
$controllerA_Scope1 = $scope1->get(AppController::class);
$controllerB_Scope1 = $scope1->get(AppController::class);

// Resolving AppController in Scope 2
$controllerC_Scope2 = $scope2->get(AppController::class);

echo "1. SINGLETON TEST (DatabaseConnection):\n";
echo "Instance in A (Scope 1): " . spl_object_id($controllerA_Scope1->db) . "\n";
echo "Instance in B (Scope 1): " . spl_object_id($controllerB_Scope1->db) . "\n";
echo "Instance in C (Scope 2): " . spl_object_id($controllerC_Scope2->db) . "\n";
echo "-> Conclusion: The object ID is always the SAME across the entire application.\n\n";

echo "2. SCOPED TEST (RequestLogger):\n";
echo "Instance in A (Scope 1): " . spl_object_id($controllerA_Scope1->logger) . "\n";
echo "Instance in B (Scope 1): " . spl_object_id($controllerB_Scope1->logger) . "\n";
echo "Instance in C (Scope 2): " . spl_object_id($controllerC_Scope2->logger) . "\n";
echo "-> Conclusion: The ID is the SAME within Scope 1, but CHANGES in Scope 2.\n\n";

echo "3. TRANSIENT TEST (MailService):\n";
echo "Instance in A (Scope 1): " . spl_object_id($controllerA_Scope1->mailer) . "\n";
echo "Instance in B (Scope 1): " . spl_object_id($controllerB_Scope1->mailer) . "\n";
echo "Instance in C (Scope 2): " . spl_object_id($controllerC_Scope2->mailer) . "\n";
echo "-> Conclusion: The ID changes EVERY time. A new instance is always created.\n\n";
