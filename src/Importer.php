<?php
declare(strict_types=1);

/** Validation et import d'un JSON de fiches. */
final class Importer
{
    public const MAX_ROWS = 2000;

    public function __construct(private Repository $repo)
    {
    }

    /** @return array{imported:int, resumes?:int, cours?:int, errors:string[]} */
    public function import(string $json): array
    {
        $data = json_decode($json, true);
        if (!is_array($data) || ($data !== [] && !array_is_list($data))) {
            return ['imported' => 0, 'errors' => ['Le fichier doit contenir un tableau JSON valide de fiches.']];
        }
        if (count($data) > self::MAX_ROWS) {
            return ['imported' => 0, 'errors' => ['Trop de fiches (maximum ' . self::MAX_ROWS . ').']];
        }
        $imported = 0;
        $resumes = 0;
        $cours = 0;
        $errors = [];
        foreach ($data as $i => $row) {
            $n = $i + 1;
            $text = static fn (string $k): string => is_array($row) && isset($row[$k]) && is_string($row[$k]) ? trim($row[$k]) : '';
            $resume = $text('resume');
            $texteCours = $text('cours');
            // Objet {document, resume et/ou cours} sans question ni réponse : contenu du document seul, aucune fiche créée.
            $resumeOnly = ($resume !== '' || $texteCours !== '') && !isset($row['question']) && !isset($row['reponse']);
            if ($resumeOnly) {
                $titre = isset($row['document']) && is_string($row['document']) ? trim($row['document']) : '';
                $fiche = null;
                $errs = $titre === '' || mb_strlen($titre) > 255 ? ['titre du document manquant ou trop long (255 max)'] : [];
            } else {
                [$fiche, $titre, $errs] = $this->validate($row);
            }
            if ($errs) {
                $errors[] = ($resumeOnly ? 'Document' : 'Fiche') . " #$n : " . implode(' ; ', $errs);
                continue;
            }
            try {
                $documentId = $this->repo->findOrCreateDocument($titre);
                if ($fiche !== null) {
                    $fiche['document_id'] = $documentId;
                    $this->repo->createFiche($fiche);
                    $imported++;
                }
                if ($resume !== '') {
                    $this->repo->setDocumentResume($documentId, $resume);
                    $resumes++;
                }
                if ($texteCours !== '') {
                    $this->repo->setDocumentCours($documentId, $texteCours);
                    $cours++;
                }
            } catch (Throwable $e) {
                $errors[] = ($resumeOnly ? 'Document' : 'Fiche') . " #$n : erreur d'enregistrement.";
            }
        }
        return ['imported' => $imported, 'resumes' => $resumes, 'cours' => $cours, 'errors' => $errors];
    }

    public function validate(mixed $row): array
    {
        if (!is_array($row)) {
            return [[], '', ['élément non valide (objet attendu)']];
        }
        $errs = [];
        $get = static fn (string $k): string => isset($row[$k]) && is_string($row[$k]) ? trim($row[$k]) : '';
        $titre = $get('document');
        foreach (['document', 'question', 'reponse'] as $req) {
            if ($get($req) === '') {
                $errs[] = "champ requis manquant ou vide : $req";
            }
        }
        $niveau = $get('niveau_maturite') ?: 'M1';
        if (!in_array($niveau, Leitner::LEVELS, true)) {
            $errs[] = 'niveau_maturite invalide (M1 à M5)';
        }
        $date = $get('date_presentation_min') ?: date('Y-m-d');
        if (!Leitner::isValidDate($date)) {
            $errs[] = 'date_presentation_min invalide (AAAA-MM-JJ)';
        }
        if (mb_strlen($titre) > 255) {
            $errs[] = 'titre du document trop long (255 max)';
        }
        $par = $get('paragraphe_reference');
        return [[
            'question' => $get('question'),
            'reponse' => $get('reponse'),
            'niveau_maturite' => $niveau,
            'date_presentation_min' => $date,
            'paragraphe_reference' => $par === '' ? null : $par,
        ], $titre, $errs];
    }
}
