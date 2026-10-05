<?php
declare(strict_types=1);

final class DocumentController extends Controller
{
    public function index(): void
    {
        $documents = $this->repo->documents();
        foreach ($documents as &$d) {
            $d['audio_url'] = $this->audioUrl($d['titre']);
        }
        unset($d);
        $this->render('documents/index', ['title' => 'Documents', 'documents' => $documents]);
    }

    /** Adresse du MP3 du cours : « <AUDIO_BASE_URL>/<titre du document>.mp3 », null si la racine n'est pas configurée. */
    private function audioUrl(string $titre): ?string
    {
        $base = (string) ($this->config['audio_base_url'] ?? '');
        return str_starts_with($base, 'https://') ? $base . '/' . rawurlencode($titre) . '.mp3' : null;
    }

    public function show(): void
    {
        $id = $this->intOrNull($_GET['id'] ?? null);
        $document = $id ? $this->repo->document($id) : null;
        if (!$document) {
            $this->flash('Document introuvable.', 'err');
            $this->redirect('index.php?page=documents');
        }
        $has = static fn (string $k): bool => $document[$k] !== null && $document[$k] !== '';
        $vue = ($_GET['vue'] ?? '') === 'cours' ? 'cours' : 'resume';
        // Sans choix explicite, on affiche le contenu disponible.
        if (!isset($_GET['vue']) && !$has('resume') && $has('cours')) {
            $vue = 'cours';
        }
        $chapitres = $vue === 'cours' && $has('cours') ? Markdown::chapters($document['cours']) : [];
        $this->render('documents/show', [
            'title' => $document['titre'], 'document' => $document, 'vue' => $vue, 'chapitres' => $chapitres,
            'audioUrl' => $this->audioUrl($document['titre']),
        ]);
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
