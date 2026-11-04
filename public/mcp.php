<?php
declare(strict_types=1);

/**
 * Point d'entrée du serveur MCP (Streamable HTTP). Accès par jeton : « Authorization: Bearer <MCP_TOKEN> ».
 * Pas de session ni de cookie : ce n'est pas l'interface web.
 */
require dirname(__DIR__) . '/src/autoload.php';
$config = require dirname(__DIR__) . '/config.php';
date_default_timezone_set($config['timezone']);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$fail = static function (int $code, string $message, array $headers = []): never {
    http_response_code($code);
    foreach ($headers as $h) {
        header($h);
    }
    exit(json_encode(['error' => $message], JSON_UNESCAPED_UNICODE));
};

$token = (string) ($config['mcp']['token'] ?? '');
if (strlen($token) < 32) {
    $fail(404, 'Serveur MCP désactivé (MCP_TOKEN absent ou de moins de 32 caractères).');
}

// Selon la configuration PHP/Apache, l'en-tête Authorization arrive sous des noms différents.
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
if ($auth === '' && function_exists('getallheaders')) {
    foreach (getallheaders() as $name => $value) {
        if (strcasecmp($name, 'Authorization') === 0) {
            $auth = (string) $value;
        }
    }
}
$given = preg_match('/^Bearer\s+(\S+)$/i', $auth, $m) ? $m[1] : '';
if (!hash_equals($token, $given)) {
    $fail(401, 'Jeton invalide.', ['WWW-Authenticate: Bearer']);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    $fail(405, 'Méthode non autorisée : utiliser POST.', ['Allow: POST']);
}

$body = file_get_contents('php://input', false, null, 0, 1_000_001);
if ($body === false || strlen($body) > 1_000_000) {
    $fail(413, 'Requête trop volumineuse.');
}

try {
    $server = new McpServer(new Repository(Database::connect($config['db'])));
    $response = $server->handle($body);
} catch (Throwable $e) {
    error_log('MCP : ' . $e->getMessage());
    $fail(500, 'Erreur interne.');
}

if ($response === null) {
    http_response_code(202); // notification : pas de contenu
    exit;
}
echo $response;
