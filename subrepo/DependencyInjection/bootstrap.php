<?php
require_once 'Container.php';
// require_once dos arquivos das classes acima...

$container = new Container();

// 1. We teach the container how to resolve dependencies
$container->addSingleton(DatabaseConnection::class);

// When UserRepositoryInterface is requested, deliver MySQLUserRepository
$container->addScoped(UserRepositoryInterface::class, MySQLUserRepository::class);

// When MailServiceInterface is requested, deliver SmtpMailService
$container->addTransient(MailServiceInterface::class, SmtpMailService::class);

// (Optional) We can register the Service and Controller, but since they don't use interfaces,
// our get() method can already resolve them using the autowiring fallback.

// ---------------------------------------------------------
// 3. Execution (Simulating your Framework's router)
// ---------------------------------------------------------

// The Router detected that the URL is "/users/store" and it maps to the UserController.
// It asks the Container to create the Controller.
// THE MAGIC HAPPENS HERE: The Container reads UserController's constructor,
// creates the Service, which creates the Repo and Mailer, which creates the DB Connection, and wires EVERYTHING by itself!

$controller = $container->get(UserController::class);

// Simulates data coming from a $_POST
$requestData = [
    'name' => 'Maria Silva',
    'email' => 'maria@exemplo.com'
];

// Executes the action
$controller->store($requestData);
