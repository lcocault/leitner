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
            if (!$file || $file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) {
                $report = ['imported' => 0, 'errors' => ['Fichier manquant, invalide ou supérieur à 5 Mo.']];
            } else {
                $report = (new Importer($this->repo))->import((string) file_get_contents($file['tmp_name']));
            }
        }
        $this->render('import', ['title' => 'Import JSON', 'report' => $report]);
    }
}
