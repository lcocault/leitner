<?php
declare(strict_types=1);

/** Session, authentification mono-utilisateur et CSRF. */
final class Security
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => !empty($_SERVER['HTTPS']),
            ]);
            session_start();
        }
    }

    public static function login(string $password, array $cfg): bool
    {
        $ok = false;
        if ($cfg['password_hash'] !== '') {
            $ok = password_verify($password, $cfg['password_hash']);
        } elseif ($cfg['password'] !== '') {
            $ok = hash_equals($cfg['password'], $password);
        }
        if ($ok) {
            session_regenerate_id(true);
            $_SESSION['auth'] = true;
        }
        return $ok;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function isLogged(): bool
    {
        return !empty($_SESSION['auth']);
    }

    public static function token(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function checkCsrf(): bool
    {
        $sent = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return is_string($sent) && hash_equals(self::token(), $sent);
    }
}
