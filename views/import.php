<h1>Import JSON</h1>
<?php if ($report): ?>
  <div class="card">
    <p class="flash <?= $report['imported'] + ($report['resumes'] ?? 0) + ($report['cours'] ?? 0) > 0 ? 'ok' : 'err' ?>"><?= (int) $report['imported'] ?> fiche(s) importée(s), <?= (int) ($report['resumes'] ?? 0) ?> résumé(s) et <?= (int) ($report['cours'] ?? 0) ?> cours enregistré(s), <?= count($report['errors']) ?> erreur(s).</p>
    <?php if ($report['errors']): ?><ul><?php foreach ($report['errors'] as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul><?php endif; ?>
  </div>
<?php endif; ?>
<form method="post" enctype="multipart/form-data" class="card">
  <?= csrf_field() ?>
  <label>Fichier JSON <input type="file" name="fichier" accept=".json,application/json" required></label>
  <button class="btn">Importer</button>
</form>
<div class="card">
  <h2>Format attendu</h2>
  <p>Un tableau d'objets. Champs requis : <code>document</code>, <code>question</code>, <code>reponse</code>. Optionnels : <code>niveau_maturite</code> (M1–M5, défaut M1), <code>date_presentation_min</code> (AAAA-MM-JJ, défaut aujourd'hui), <code>paragraphe_reference</code>. Le document est créé s'il n'existe pas.</p>
  <p><strong>Catégorie</strong> : le champ <code>categorie</code> (ex. « CAP Esthétique - Pole 1 ») est <strong>requis lorsque l'objet crée un nouveau document</strong>. Il est facultatif pour un document existant, qu'il reclasse s'il est renseigné. Il suffit de le mettre dans l'objet « document » (celui qui porte <code>resume</code> / <code>cours</code>), à placer en premier dans le fichier.</p>
  <p>Résumé et cours complet d'un document : ajoutez les champs <code>resume</code> et/ou <code>cours</code> (Markdown). Un objet sans <code>question</code> ni <code>reponse</code> enregistre ces contenus sans créer de fiche ; les réimporter remplace les précédents.</p>
  <pre><code>[
  {
    "document": "Titre du document de référence",
    "question": "...",
    "reponse": "... (markdown)",
    "niveau_maturite": "M1",
    "date_presentation_min": "2026-10-03",
    "paragraphe_reference": "..."
  },
  {
    "document": "Titre du document de référence",
    "categorie": "CAP Esthétique - Pole 1",
    "resume": "... (markdown)",
    "cours": "... (markdown)"
  }
]</code></pre>
</div>
<div class="card">
  <h2>Prompt à utiliser avec un LLM pour générer des fiches à partir d'un PDF</h2>
  <button class="btn small" type="button" id="copy-prompt">Copier</button>
  <textarea id="prompt" rows="16" readonly>Tu es un assistant pédagogique. À partir du document PDF suivant, génère une liste de 
fiches de révision au format JSON strictement conforme au schéma suivant :
[
  {
    "document": "<titre exact du document>",
    "question": "<question claire et précise>",
    "reponse": "<réponse complète en Markdown, utilise des tableaux Markdown si pertinent>",
    "niveau_maturite": "M1",
    "date_presentation_min": "<date du jour au format AAAA-MM-JJ>",
    "paragraphe_reference": "<paragraphe ou section du document source>"
  }
]
Si le document est nouveau, ajoute d'abord un objet {"document": "<titre exact>", "categorie": "<catégorie, ex. CAP Esthétique - Pole 1>"}.
Génère entre 10 et 30 fiches couvrant les points clés du document, une fiche par notion 
distincte. Réponds uniquement avec le JSON, sans texte d'accompagnement.</textarea>
</div>
