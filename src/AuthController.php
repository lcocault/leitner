<?php
declare(strict_types=1);

final class AuthController extends Controller
{
    public function login(): void
    {
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requirePost();
            if (Security::login((string) ($_POST['password'] ?? ''), $this->config['auth'])) {
                $this->redirect('index.php?page=revision');
            }
            usleep(500000);
            $error = 'Mot de passe incorrect.';
        }
        $this->render('login', ['title' => 'Connexion', 'error' => $error]);
    }

    public function logout(): void
    {
        $this->requirePost();
        Security::logout();
        $this->redirect('index.php?page=login');
    }
}
