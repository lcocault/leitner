<h1><?= e($title) ?></h1>
<?php foreach ($errors as $err): ?><p class="flash err"><?= e($err) ?></p><?php endforeach; ?>
<form method="post" action="index.php?page=fiches&action=save" class="card">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int) $fiche['id'] ?>">
  <label>Document
    <select name="document_id"><option value="">— choisir —</option>
      <?php foreach ($documents as $d): ?><option value="<?= (int) $d['id'] ?>" <?= (int) $fiche['document_id'] === (int) $d['id'] ? 'selected' : '' ?>><?= e($d['titre']) ?></option><?php endforeach; ?>
    </select></label>
  <label>…ou nouveau document <input name="nouveau_document" maxlength="255"></label>
  <label>Question (markdown) <textarea name="question" rows="4" required data-preview="#pq"><?= e($fiche['question']) ?></textarea></label>
  <div id="pq" class="md preview"></div>
  <label>Réponse (markdown, images et tableaux admis) <textarea name="reponse" rows="8" required data-preview="#pr"><?= e($fiche['reponse']) ?></textarea></label>
  <div id="pr" class="md preview"></div>
  <label>Niveau de maturité
    <select name="niveau_maturite"><?php foreach (Leitner::LEVELS as $l): ?><option <?= $fiche['niveau_maturite'] === $l ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
  <label>Date de présentation minimale <input type="date" name="date_presentation_min" value="<?= e(substr((string) $fiche['date_presentation_min'], 0, 10)) ?>" required></label>
  <label>Paragraphe de référence <input name="paragraphe_reference" value="<?= e($fiche['paragraphe_reference']) ?>"></label>
  <button class="btn">Enregistrer</button>
  <a href="index.php?page=fiches">Annuler</a>
</form>
<meta name="csrf" content="<?= e(Security::token()) ?>">
