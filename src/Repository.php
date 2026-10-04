<?php
declare(strict_types=1);

/** Accès données (PDO, requêtes préparées uniquement). */
final class Repository
{
    public function __construct(private PDO $db)
    {
    }

    // ---- Documents
    public function documents(): array
    {
        return $this->db->query(
            'SELECT d.id, d.titre, d.created_at, (d.resume IS NOT NULL) AS a_resume, COUNT(f.id) AS nb_fiches
             FROM documents d LEFT JOIN fiches f ON f.document_id = d.id
             GROUP BY d.id, d.titre, d.created_at, d.resume ORDER BY d.titre'
        )->fetchAll();
    }

    public function document(int $id): ?array
    {
        $s = $this->db->prepare('SELECT * FROM documents WHERE id = ?');
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }

    public function setDocumentResume(int $id, string $resume): void
    {
        $this->db->prepare('UPDATE documents SET resume = ? WHERE id = ?')->execute([$resume, $id]);
    }

    public function documentByTitle(string $titre): ?array
    {
        $s = $this->db->prepare('SELECT * FROM documents WHERE titre = ?');
        $s->execute([$titre]);
        return $s->fetch() ?: null;
    }

    public function documentExists(int $id): bool
    {
        $s = $this->db->prepare('SELECT 1 FROM documents WHERE id = ?');
        $s->execute([$id]);
        return (bool) $s->fetchColumn();
    }

    public function findOrCreateDocument(string $titre): int
    {
        $d = $this->documentByTitle($titre);
        if ($d) {
            return (int) $d['id'];
        }
        $s = $this->db->prepare('INSERT INTO documents (titre) VALUES (?) RETURNING id');
        $s->execute([$titre]);
        return (int) $s->fetchColumn();
    }

    public function countFichesOfDocument(int $id): int
    {
        $s = $this->db->prepare('SELECT COUNT(*) FROM fiches WHERE document_id = ?');
        $s->execute([$id]);
        return (int) $s->fetchColumn();
    }

    public function deleteDocument(int $id, bool $cascade): void
    {
        $this->db->beginTransaction();
        try {
            if ($cascade) {
                $this->db->prepare('DELETE FROM fiches WHERE document_id = ?')->execute([$id]);
            }
            $this->db->prepare('DELETE FROM documents WHERE id = ?')->execute([$id]);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ---- Fiches
    private function filter(?int $doc, ?string $niveau, array &$params): string
    {
        $w = [];
        if ($doc) {
            $w[] = 'f.document_id = ?';
            $params[] = $doc;
        }
        if ($niveau) {
            $w[] = 'f.niveau_maturite = ?';
            $params[] = $niveau;
        }
        return $w ? 'WHERE ' . implode(' AND ', $w) : '';
    }

    public function countFiches(?int $doc, ?string $niveau): int
    {
        $p = [];
        $where = $this->filter($doc, $niveau, $p);
        $s = $this->db->prepare("SELECT COUNT(*) FROM fiches f $where");
        $s->execute($p);
        return (int) $s->fetchColumn();
    }

    public function listFiches(?int $doc, ?string $niveau, int $limit, int $offset): array
    {
        $p = [];
        $where = $this->filter($doc, $niveau, $p);
        $s = $this->db->prepare(
            "SELECT f.*, d.titre AS document_titre FROM fiches f JOIN documents d ON d.id = f.document_id
             $where ORDER BY f.id DESC LIMIT ? OFFSET ?"
        );
        $p[] = $limit;
        $p[] = $offset;
        foreach ($p as $k => $v) {
            $s->bindValue($k + 1, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $s->execute();
        return $s->fetchAll();
    }

    public function fiche(int $id): ?array
    {
        $s = $this->db->prepare('SELECT f.*, d.titre AS document_titre FROM fiches f JOIN documents d ON d.id = f.document_id WHERE f.id = ?');
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }

    public function createFiche(array $f): int
    {
        $s = $this->db->prepare(
            'INSERT INTO fiches (document_id, question, reponse, niveau_maturite, date_presentation_min, paragraphe_reference)
             VALUES (?, ?, ?, ?, ?, ?) RETURNING id'
        );
        $s->execute([$f['document_id'], $f['question'], $f['reponse'], $f['niveau_maturite'], $f['date_presentation_min'], $f['paragraphe_reference']]);
        return (int) $s->fetchColumn();
    }

    public function updateFiche(int $id, array $f): void
    {
        $this->db->prepare(
            'UPDATE fiches SET document_id = ?, question = ?, reponse = ?, niveau_maturite = ?,
             date_presentation_min = ?, paragraphe_reference = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?'
        )->execute([$f['document_id'], $f['question'], $f['reponse'], $f['niveau_maturite'], $f['date_presentation_min'], $f['paragraphe_reference'], $id]);
    }

    public function deleteFiche(int $id): void
    {
        $this->db->prepare('DELETE FROM fiches WHERE id = ?')->execute([$id]);
    }

    // ---- Révision
    public function randomDue(string $today, ?int $doc): ?array
    {
        $sql = 'SELECT f.*, d.titre AS document_titre FROM fiches f JOIN documents d ON d.id = f.document_id
                WHERE f.date_presentation_min <= ?';
        $p = [$today];
        if ($doc) {
            $sql .= ' AND f.document_id = ?';
            $p[] = $doc;
        }
        $s = $this->db->prepare($sql . ' ORDER BY RANDOM() LIMIT 1');
        $s->execute($p);
        return $s->fetch() ?: null;
    }

    public function nextDueDate(string $today, ?int $doc): ?string
    {
        $sql = 'SELECT MIN(date_presentation_min) FROM fiches WHERE date_presentation_min > ?';
        $p = [$today];
        if ($doc) {
            $sql .= ' AND document_id = ?';
            $p[] = $doc;
        }
        $s = $this->db->prepare($sql);
        $s->execute($p);
        $v = $s->fetchColumn();
        return $v ? substr((string) $v, 0, 10) : null;
    }

    public function recordRevision(int $ficheId, string $result, string $from, string $to, string $nextDate): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare('UPDATE fiches SET niveau_maturite = ?, date_presentation_min = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?')
                ->execute([$to, $nextDate, $ficheId]);
            $this->db->prepare('INSERT INTO historique_revisions (fiche_id, resultat, niveau_avant, niveau_apres) VALUES (?, ?, ?, ?)')
                ->execute([$ficheId, $result, $from, $to]);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ---- Dashboard
    public function matrix(): array
    {
        return $this->db->query(
            'SELECT d.id, d.titre, f.niveau_maturite AS niveau, COUNT(f.id) AS nb
             FROM documents d LEFT JOIN fiches f ON f.document_id = d.id
             GROUP BY d.id, d.titre, f.niveau_maturite ORDER BY d.titre'
        )->fetchAll();
    }
}
