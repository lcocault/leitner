<?php
declare(strict_types=1);

abstract class Controller
{
    public function __construct(protected Repository $repo, protected array $config)
    {
    }

    protected function render(string $view, array $vars = []): void
    {
        $file = dirname(__DIR__) . '/views/' . $view . '.php';
        extract($vars, EXTR_SKIP);
        ob_start();
        require $file;
        $content = ob_get_clean();
        $title = $vars['title'] ?? 'Leitner';
        $logged = Security::isLogged();
        require dirname(__DIR__) . '/views/layout.php';
    }

    protected function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    protected function flash(string $msg, string $type = 'ok'): void
    {
        $_SESSION['flash'] = [$type, $msg];
    }

    protected function requirePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Security::checkCsrf()) {
            http_response_code(400);
            exit('Requête invalide (jeton CSRF).');
        }
    }

    protected function intOrNull(mixed $v): ?int
    {
        return is_string($v) && ctype_digit($v) && (int) $v > 0 ? (int) $v : null;
    }

    protected function today(): string
    {
        return date('Y-m-d');
    }
}
