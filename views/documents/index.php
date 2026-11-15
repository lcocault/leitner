<h1>Documents de référence</h1>
<form method="post" action="index.php?page=documents&action=create" class="card">
  <?= csrf_field() ?>
  <label>Nouveau document <input name="titre" maxlength="255" required></label>
  <label>Catégorie <input name="categorie" list="liste-categories" maxlength="255" required></label>
  <button class="btn">Créer</button>
</form>
<datalist id="liste-categories"><?php foreach ($categories as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist>
<?php foreach (par_categorie($documents) as $nomCategorie => $docs): ?>
<h2><?= e($nomCategorie) ?> <span class="meta">(<?= count($docs) ?>)</span></h2>
<div class="table-wrap">
<table>
  <thead><tr><th>Titre</th><th>Lecture</th><th>Audio</th><th>Fiches</th><th>Catégorie</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($docs as $d): ?>
    <tr><td><?= e($d['titre']) ?></td>
      <td class="nowrap">
        <?php if ($d['a_resume']): ?><a href="index.php?page=documents&action=show&id=<?= (int) $d['id'] ?>&vue=resume">Résumé</a><?php endif; ?>
        <?php if ($d['a_resume'] && $d['a_cours']): ?> · <?php endif; ?>
        <?php if ($d['a_cours']): ?><a href="index.php?page=documents&action=show&id=<?= (int) $d['id'] ?>&vue=cours">Cours</a><?php endif; ?>
        <?php if (!$d['a_resume'] && !$d['a_cours']): ?>—<?php endif; ?>
      </td>
      <td><?php if ($d['audio_url'] !== null): ?><a href="<?= e($d['audio_url']) ?>" download>MP3</a><?php else: ?>—<?php endif; ?></td>
      <td><?= (int) $d['nb_fiches'] ?></td>
      <td>
        <form method="post" action="index.php?page=documents&action=categorie" class="inline">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
          <input name="categorie" list="liste-categories" maxlength="255" value="<?= e($d['categorie'] ?? '') ?>" aria-label="Catégorie">
          <button class="btn small">OK</button>
        </form>
      </td>
      <td>
        <form method="post" action="index.php?page=documents&action=delete" class="inline" onsubmit="return confirm('Supprimer ce document ?')">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
          <?php if ((int) $d['nb_fiches'] > 0): ?><label class="inline"><input type="checkbox" name="cascade" value="1"> avec ses fiches</label><?php endif; ?>
          <button class="btn bad small">Supprimer</button>
        </form>
      </td></tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endforeach; ?>
