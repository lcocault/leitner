<?php
declare(strict_types=1);

final class RevisionController extends Controller
{
    public function index(): void
    {
        $docId = $this->intOrNull($_GET['document_id'] ?? null);
        $categorie = $this->categorieOrNull($_GET['categorie'] ?? null);
        $today = $this->today();
        $fiche = $this->repo->randomDue($today, $docId, $categorie);
        $this->render('revision', [
            'title' => 'Révision',
            'documents' => $this->repo->documents(),
            'categories' => $this->repo->categories(),
            'docId' => $docId,
            'categorie' => $categorie,
            'fiche' => $fiche,
            'next' => $fiche ? null : $this->repo->nextDueDate($today, $docId, $categorie),
        ]);
    }

    public function answer(): void
    {
        $this->requirePost();
        $docId = $this->intOrNull($_POST['document_id'] ?? null);
        $categorie = $this->categorieOrNull($_POST['categorie'] ?? null);
        $fiche = $this->repo->fiche((int) ($_POST['fiche_id'] ?? 0));
        $result = $_POST['resultat'] ?? '';
        if (!$fiche || !in_array($result, Leitner::RESULTS, true)) {
            $this->flash('Réponse invalide.', 'err');
        } else {
            $new = Leitner::nextLevel($fiche['niveau_maturite'], $result);
            $this->repo->recordRevision((int) $fiche['id'], $result, $fiche['niveau_maturite'], $new, Leitner::nextDate($new));
        }
        $this->redirect('index.php?page=revision' . ($docId ? '&document_id=' . $docId : '') . ($categorie !== null ? '&categorie=' . rawurlencode($categorie) : ''));
    }

    private function categorieOrNull(mixed $v): ?string
    {
        return is_string($v) && trim($v) !== '' ? trim($v) : null;
    }
}
