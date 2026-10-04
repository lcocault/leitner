<?php
declare(strict_types=1);

final class DocumentController extends Controller
{
    public function index(): void
    {
        $this->render('documents/index', ['title' => 'Documents', 'documents' => $this->repo->documents()]);
    }

    public function show(): void
    {
        $id = $this->intOrNull($_GET['id'] ?? null);
        $document = $id ? $this->repo->document($id) : null;
        if (!$document) {
            $this->flash('Document introuvable.', 'err');
            $this->redirect('index.php?page=documents');
        }
        $this->render('documents/show', ['title' => $document['titre'], 'document' => $document]);
    }

    public function create(): void
    {
        $this->requirePost();
        $titre = trim((string) ($_POST['titre'] ?? ''));
        if ($titre === '' || mb_strlen($titre) > 255) {
            $this->flash('Titre invalide.', 'err');
        } elseif ($this->repo->documentByTitle($titre)) {
            $this->flash('Ce document existe déjà.', 'err');
        } else {
            $this->repo->findOrCreateDocument($titre);
            $this->flash('Document créé.');
        }
        $this->redirect('index.php?page=documents');
    }

    public function delete(): void
    {
        $this->requirePost();
        $id = $this->intOrNull($_POST['id'] ?? null);
        $cascade = !empty($_POST['cascade']);
        if (!$id || !$this->repo->documentExists($id)) {
            $this->flash('Document introuvable.', 'err');
        } elseif (!$cascade && $this->repo->countFichesOfDocument($id) > 0) {
            $this->flash('Suppression impossible : des fiches sont rattachées (cochez la suppression en cascade).', 'err');
        } else {
            $this->repo->deleteDocument($id, $cascade);
            $this->flash('Document supprimé.');
        }
        $this->redirect('index.php?page=documents');
    }
}
