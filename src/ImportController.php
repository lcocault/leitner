<?php
declare(strict_types=1);

final class ImportController extends Controller
{
    public function index(): void
    {
        $report = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requirePost();
            $file = $_FILES['fichier'] ?? null;
            $error = $this->uploadError($file);
            if ($error !== null) {
                $report = ['imported' => 0, 'errors' => [$error]];
            } else {
                $report = (new Importer($this->repo))->import((string) file_get_contents($file['tmp_name']));
            }
        }
        $this->render('import', ['title' => 'Import JSON', 'report' => $report]);
    }

    /** Message précis selon la cause du rejet de l'envoi, null si le fichier est exploitable. */
    private function uploadError(?array $file): ?string
    {
        if ($file === null) {
            // $_FILES vide sur un POST : le corps de la requête dépasse post_max_size.
            return 'Aucun fichier reçu : la requête dépasse probablement post_max_size (' . ini_get('post_max_size') . ').';
        }
        return match ($file['error']) {
            UPLOAD_ERR_OK => $file['size'] > 5 * 1024 * 1024 ? 'Fichier supérieur à 5 Mo.' : null,
            UPLOAD_ERR_INI_SIZE => 'Fichier supérieur à upload_max_filesize (' . ini_get('upload_max_filesize') . ') du serveur PHP.',
            UPLOAD_ERR_FORM_SIZE => 'Fichier supérieur à la taille autorisée par le formulaire.',
            UPLOAD_ERR_PARTIAL => 'Fichier reçu partiellement, réessayez.',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier sélectionné.',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant sur le serveur (upload_tmp_dir).',
            UPLOAD_ERR_CANT_WRITE => "Impossible d'écrire le fichier sur le disque du serveur.",
            UPLOAD_ERR_EXTENSION => 'Envoi bloqué par une extension PHP.',
            default => "Erreur d'envoi inconnue (code {$file['error']}).",
        };
    }
}
