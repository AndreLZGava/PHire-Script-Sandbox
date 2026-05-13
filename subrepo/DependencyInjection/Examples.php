<?php

// ---------------------------------------------------------
// Interfaces (Contratos)
// ---------------------------------------------------------
interface UserRepositoryInterface {
    public function save(array $data): bool;
}

interface MailServiceInterface {
    public function send(string $to, string $subject): void;
}

// ---------------------------------------------------------
// Concrete Implementations
// ---------------------------------------------------------

// Singleton: Database connection (expensive, we want only one)
class DatabaseConnection {
    public function execute(string $sql) {
        // Simulates execution on the database
        echo "[DB] Executando: {$sql}\n";
    }
}

// Scoped: Repository that uses the connection
class MySQLUserRepository implements UserRepositoryInterface {
    private DatabaseConnection $db;

    public function __construct(DatabaseConnection $db) {
        $this->db = $db;
    }

    public function save(array $data): bool {
        $this->db->execute("INSERT INTO users (name, email) VALUES (...)");
        return true;
    }
}

// Transient: Email sending service
class SmtpMailService implements MailServiceInterface {
    public function send(string $to, string $subject): void {
        echo "[MAIL] Enviando email para {$to} - Assunto: {$subject}\n";
    }
}

// ---------------------------------------------------------
// Business Rule and Controller
// ---------------------------------------------------------

// The service that orchestrates the business logic
class UserRegistrationService {
    private UserRepositoryInterface $userRepository;
    private MailServiceInterface $mailService;

    // We request the INTERFACES, the Container will inject the concrete classes!
    public function __construct(UserRepositoryInterface $userRepository, MailServiceInterface $mailService) {
        $this->userRepository = $userRepository;
        $this->mailService = $mailService;
    }

    public function registerUser(string $name, string $email): void {
        $this->userRepository->save(['name' => $name, 'email' => $email]);
        $this->mailService->send($email, "Welcome to the system, {$name}!");
    }
}

// The Controller that handles the simulated HTTP request
class UserController {
    private UserRegistrationService $registrationService;

    public function __construct(UserRegistrationService $registrationService) {
        $this->registrationService = $registrationService;
    }

    public function store(array $requestData): void {
        echo "Starting request in UserController...\n";
        $this->registrationService->registerUser($requestData['name'], $requestData['email']);
        echo "Request completed successfully!\n";
    }
}
