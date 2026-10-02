<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/autoload.php';
$config = require dirname(__DIR__) . '/config.php';
date_default_timezone_set($config['timezone']);
Security::start();

$page = (string) ($_GET['page'] ?? 'revision');
$action = (string) ($_GET['action'] ?? 'index');

try {
    $repo = new Repository(Database::connect($config['db']));
} catch (Throwable $e) {
    http_response_code(500);
    exit('Connexion à la base de données impossible.');
}

$routes = [
    'login' => [AuthController::class, ['index' => 'login'], false],
    'logout' => [AuthController::class, ['index' => 'logout']],
    'revision' => [RevisionController::class, ['index' => 'index', 'answer' => 'answer']],
    'dashboard' => [DashboardController::class, ['index' => 'index']],
    'fiches' => [FicheController::class, ['index' => 'index', 'form' => 'form', 'save' => 'save', 'delete' => 'delete', 'preview' => 'preview']],
    'documents' => [DocumentController::class, ['index' => 'index', 'create' => 'create', 'delete' => 'delete']],
    'import' => [ImportController::class, ['index' => 'index']],
];

if (!isset($routes[$page]) || !isset($routes[$page][1][$action])) {
    http_response_code(404);
    exit('Page introuvable.');
}
[$class, $actions, $protected] = $routes[$page] + [2 => true];
if ($protected && !Security::isLogged()) {
    header('Location: index.php?page=login');
    exit;
}
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
(new $class($repo, $config))->{$actions[$action]}();
