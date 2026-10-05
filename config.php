<?php
declare(strict_types=1);

/**
 * Configuration : tout est externalisé via variables d'environnement
 * (ou un fichier .env à la racine, non versionné).
 */
$env = static function (string $key, ?string $default = null): ?string {
    $v = getenv($key);
    if ($v !== false && $v !== '') {
        return $v;
    }
    static $file = null;
    if ($file === null) {
        $file = [];
        $path = __DIR__ . '/.env';
        if (is_readable($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                    continue;
                }
                [$k, $val] = explode('=', $line, 2);
                $file[trim($k)] = trim($val, " \t\"'");
            }
        }
    }
    return $file[$key] ?? $default;
};

$host = $env('DB_HOST', 'localhost');
$port = $env('DB_PORT', '5432');
$name = $env('DB_NAME', 'leitner');

return [
    'db' => [
        'dsn' => $env('DB_DSN', "pgsql:host=$host;port=$port;dbname=$name"),
        'user' => $env('DB_USER', 'postgres'),
        'password' => $env('DB_PASSWORD', ''),
    ],
    'auth' => [
        'password_hash' => $env('APP_PASSWORD_HASH', ''),
        'password' => $env('APP_PASSWORD', ''), // alternative en clair, déconseillée
    ],
    'timezone' => $env('APP_TIMEZONE', 'Europe/Paris'),
    // Racine HTTPS des versions audio des cours : le fichier attendu est « <titre du document>.mp3 ».
    'audio_base_url' => rtrim((string) $env('AUDIO_BASE_URL', ''), '/'),
    'per_page' => 20,
];
