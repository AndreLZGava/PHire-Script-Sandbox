<?php
require_once 'Container.php';
// require_once dos arquivos das classes acima...

$container = new Container();

// 1. Ensinamos o container como resolver as dependências
$container->addSingleton(DatabaseConnection::class);

// Quando alguém pedir a UserRepositoryInterface, entregue o MySQLUserRepository
$container->addScoped(UserRepositoryInterface::class, MySQLUserRepository::class);

// Quando alguém pedir a MailServiceInterface, entregue o SmtpMailService
$container->addTransient(MailServiceInterface::class, SmtpMailService::class);

// (Opcional) Podemos registrar o Service e o Controller, mas como não usam interface,
// o nosso método get() já consegue resolvê-los usando o fallback do autowiring.

// ---------------------------------------------------------
// 3. A Execução (Simulando o roteador do seu Framework)
// ---------------------------------------------------------

// O Roteador percebeu que a URL é "/users/store" e que ela aponta para o UserController.
// Ele pede ao Container para criar o Controller.
// A MÁGICA ACONTECE AQUI: O Container lê o construtor do UserController,
// cria o Service, que cria o Repo e o Mailer, que cria a Conexão DB, e monta TUDO sozinho!

$controller = $container->get(UserController::class);

// Simula os dados vindos de um $_POST
$requestData = [
    'name' => 'Maria Silva',
    'email' => 'maria@exemplo.com'
];

// Executa a ação
$controller->store($requestData);
