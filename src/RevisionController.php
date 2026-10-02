<?php
declare(strict_types=1);

final class RevisionController extends Controller
{
    public function index(): void
    {
        $docId = $this->intOrNull($_GET['document_id'] ?? null);
        $today = $this->today();
        $fiche = $this->repo->randomDue($today, $docId);
        $this->render('revision', [
            'title' => 'Révision',
            'documents' => $this->repo->documents(),
            'docId' => $docId,
            'fiche' => $fiche,
            'next' => $fiche ? null : $this->repo->nextDueDate($today, $docId),
        ]);
    }

    public function answer(): void
    {
        $this->requirePost();
        $docId = $this->intOrNull($_POST['document_id'] ?? null);
        $fiche = $this->repo->fiche((int) ($_POST['fiche_id'] ?? 0));
        $result = $_POST['resultat'] ?? '';
        if (!$fiche || !in_array($result, Leitner::RESULTS, true)) {
            $this->flash('Réponse invalide.', 'err');
        } else {
            $new = Leitner::nextLevel($fiche['niveau_maturite'], $result);
            $this->repo->recordRevision((int) $fiche['id'], $result, $fiche['niveau_maturite'], $new, Leitner::nextDate($new));
        }
        $this->redirect('index.php?page=revision' . ($docId ? '&document_id=' . $docId : ''));
    }
}
