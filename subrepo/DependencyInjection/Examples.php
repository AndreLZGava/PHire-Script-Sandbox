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
// Implementações Concretas
// ---------------------------------------------------------

// Singleton: Conexão com o banco (Pesada, queremos apenas uma)
class DatabaseConnection {
    public function execute(string $sql) {
        // Finge que está executando no banco
        echo "[DB] Executando: {$sql}\n";
    }
}

// Scoped: Repositório que usa a conexão
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

// Transient: Serviço de envio de email
class SmtpMailService implements MailServiceInterface {
    public function send(string $to, string $subject): void {
        echo "[MAIL] Enviando email para {$to} - Assunto: {$subject}\n";
    }
}

// ---------------------------------------------------------
// Regra de Negócio e Controller
// ---------------------------------------------------------

// O serviço que orquestra a lógica de negócio
class UserRegistrationService {
    private UserRepositoryInterface $userRepository;
    private MailServiceInterface $mailService;

    // Pedimos as INTERFACES, o Container vai injetar as classes concretas!
    public function __construct(UserRepositoryInterface $userRepository, MailServiceInterface $mailService) {
        $this->userRepository = $userRepository;
        $this->mailService = $mailService;
    }

    public function registerUser(string $name, string $email): void {
        $this->userRepository->save(['name' => $name, 'email' => $email]);
        $this->mailService->send($email, "Bem-vindo ao sistema, {$name}!");
    }
}

// O Controller que recebe a requisição HTTP (simulada)
class UserController {
    private UserRegistrationService $registrationService;

    public function __construct(UserRegistrationService $registrationService) {
        $this->registrationService = $registrationService;
    }

    public function store(array $requestData): void {
        echo "Iniciando request no UserController...\n";
        $this->registrationService->registerUser($requestData['name'], $requestData['email']);
        echo "Request finalizado com sucesso!\n";
    }
}
