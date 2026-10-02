<?php
declare(strict_types=1);

final class FicheController extends Controller
{
    public function index(): void
    {
        $doc = $this->intOrNull($_GET['document_id'] ?? null);
        $niv = in_array($_GET['niveau'] ?? '', Leitner::LEVELS, true) ? $_GET['niveau'] : null;
        $per = (int) $this->config['per_page'];
        $total = $this->repo->countFiches($doc, $niv);
        $pages = max(1, (int) ceil($total / $per));
        $page = min($pages, max(1, (int) ($_GET['p'] ?? 1)));
        $this->render('fiches/index', [
            'title' => 'Fiches',
            'fiches' => $this->repo->listFiches($doc, $niv, $per, ($page - 1) * $per),
            'documents' => $this->repo->documents(),
            'doc' => $doc, 'niv' => $niv, 'page' => $page, 'pages' => $pages, 'total' => $total,
        ]);
    }

    public function form(): void
    {
        $id = $this->intOrNull($_GET['id'] ?? null);
        $fiche = $id ? $this->repo->fiche($id) : null;
        if ($id && !$fiche) {
            http_response_code(404);
            exit('Fiche introuvable.');
        }
        $this->showForm($fiche ?? [
            'id' => null, 'document_id' => $this->intOrNull($_GET['document_id'] ?? null), 'question' => '', 'reponse' => '',
            'niveau_maturite' => 'M1', 'date_presentation_min' => $this->today(), 'paragraphe_reference' => '',
        ], []);
    }

    private function showForm(array $fiche, array $errors): void
    {
        $this->render('fiches/form', [
            'title' => $fiche['id'] ? 'Modifier la fiche' : 'Nouvelle fiche',
            'fiche' => $fiche, 'errors' => $errors, 'documents' => $this->repo->documents(),
        ]);
    }

    public function save(): void
    {
        $this->requirePost();
        $id = $this->intOrNull($_POST['id'] ?? null);
        $errors = [];
        $f = [
            'id' => $id,
            'document_id' => $this->intOrNull($_POST['document_id'] ?? null),
            'question' => trim((string) ($_POST['question'] ?? '')),
            'reponse' => trim((string) ($_POST['reponse'] ?? '')),
            'niveau_maturite' => (string) ($_POST['niveau_maturite'] ?? 'M1'),
            'date_presentation_min' => trim((string) ($_POST['date_presentation_min'] ?? '')),
            'paragraphe_reference' => trim((string) ($_POST['paragraphe_reference'] ?? '')),
        ];
        $newDoc = trim((string) ($_POST['nouveau_document'] ?? ''));
        if ($newDoc !== '') {
            if (mb_strlen($newDoc) > 255) {
                $errors[] = 'Titre de document trop long.';
            } else {
                $f['document_id'] = $this->repo->findOrCreateDocument($newDoc);
            }
        } elseif (!$f['document_id'] || !$this->repo->documentExists($f['document_id'])) {
            $errors[] = 'Choisissez un document ou saisissez-en un nouveau.';
        }
        if ($f['question'] === '') {
            $errors[] = 'La question est requise.';
        }
        if ($f['reponse'] === '') {
            $errors[] = 'La réponse est requise.';
        }
        if (!in_array($f['niveau_maturite'], Leitner::LEVELS, true)) {
            $errors[] = 'Niveau invalide.';
        }
        if (!Leitner::isValidDate($f['date_presentation_min'])) {
            $errors[] = 'Date invalide.';
        }
        if ($id && !$this->repo->fiche($id)) {
            $errors[] = 'Fiche introuvable.';
        }
        if ($errors) {
            $this->showForm($f, $errors);
            return;
        }
        $f['paragraphe_reference'] = $f['paragraphe_reference'] === '' ? null : $f['paragraphe_reference'];
        if ($id) {
            $this->repo->updateFiche($id, $f);
            $this->flash('Fiche modifiée.');
        } else {
            $this->repo->createFiche($f);
            $this->flash('Fiche créée.');
        }
        $this->redirect('index.php?page=fiches');
    }

    public function delete(): void
    {
        $this->requirePost();
        $id = $this->intOrNull($_POST['id'] ?? null);
        if ($id) {
            $this->repo->deleteFiche($id);
            $this->flash('Fiche supprimée.');
        }
        $this->redirect('index.php?page=fiches');
    }

    public function preview(): void
    {
        $this->requirePost();
        header('Content-Type: text/html; charset=utf-8');
        echo Markdown::render((string) ($_POST['text'] ?? ''));
    }
}
