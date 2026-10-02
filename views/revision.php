<h1>Réviser</h1>
<form method="get" class="filters">
  <input type="hidden" name="page" value="revision">
  <label>Document
    <select name="document_id">
      <option value="">Tous les documents</option>
      <?php foreach ($documents as $d): ?>
        <option value="<?= (int) $d['id'] ?>" <?= $docId === (int) $d['id'] ? 'selected' : '' ?>><?= e($d['titre']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <button class="btn">Réviser</button>
</form>
<?php if (!$fiche): ?>
  <div class="card">
    <p><strong>Aucune fiche à réviser pour le moment.</strong></p>
    <?php if ($next): ?>
      <p>Prochaine fiche disponible le <?= e(date('d/m/Y', strtotime($next))) ?>.</p>
    <?php else: ?>
      <p>Aucune fiche à venir. <a href="index.php?page=fiches&action=form">Créez-en une</a> ou <a href="index.php?page=import">importez-en</a>.</p>
    <?php endif; ?>
  </div>
<?php else: ?>
  <div class="card">
    <p class="meta"><?= e($fiche['document_titre']) ?> · niveau <span class="badge"><?= e($fiche['niveau_maturite']) ?></span></p>
    <h2>Question</h2>
    <div class="md"><?= Markdown::render($fiche['question']) ?></div>
    <button class="btn" id="show-answer" type="button">Afficher la réponse</button>
    <div id="answer" hidden>
      <h2>Réponse</h2>
      <div class="md"><?= Markdown::render($fiche['reponse']) ?></div>
      <?php if ($fiche['paragraphe_reference']): ?><p class="meta">Réf. : <?= e($fiche['paragraphe_reference']) ?></p><?php endif; ?>
      <form method="post" action="index.php?page=revision&action=answer" class="actions">
        <?= csrf_field() ?>
        <input type="hidden" name="fiche_id" value="<?= (int) $fiche['id'] ?>">
        <input type="hidden" name="document_id" value="<?= (int) $docId ?>">
        <button class="btn ok" name="resultat" value="correct">Correct</button>
        <button class="btn warn" name="resultat" value="incomplet">Incomplet</button>
        <button class="btn bad" name="resultat" value="incorrect">Incorrect</button>
      </form>
    </div>
  </div>
<?php endif; ?>
