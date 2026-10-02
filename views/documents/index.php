<h1>Documents de référence</h1>
<form method="post" action="index.php?page=documents&action=create" class="card">
  <?= csrf_field() ?>
  <label>Nouveau document <input name="titre" maxlength="255" required></label>
  <button class="btn">Créer</button>
</form>
<div class="table-wrap">
<table>
  <thead><tr><th>Titre</th><th>Fiches</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($documents as $d): ?>
    <tr><td><?= e($d['titre']) ?></td><td><?= (int) $d['nb_fiches'] ?></td>
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
