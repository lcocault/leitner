<h1>Fiches <span class="meta">(<?= (int) $total ?>)</span></h1>
<p><a class="btn" href="index.php?page=fiches&action=form">+ Nouvelle fiche</a></p>
<form method="get" class="filters">
  <input type="hidden" name="page" value="fiches">
  <label>Document
    <select name="document_id"><option value="">Tous</option>
      <?php foreach ($documents as $d): ?><option value="<?= (int) $d['id'] ?>" <?= $doc === (int) $d['id'] ? 'selected' : '' ?>><?= e($d['titre']) ?></option><?php endforeach; ?>
    </select></label>
  <label>Niveau
    <select name="niveau"><option value="">Tous</option>
      <?php foreach (Leitner::LEVELS as $l): ?><option <?= $niv === $l ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
    </select></label>
  <button class="btn">Filtrer</button>
</form>
<div class="table-wrap">
<table>
  <thead><tr><th>Document</th><th>Question</th><th>Niveau</th><th>Prochaine</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($fiches as $f): ?>
    <tr>
      <td><?= e($f['document_titre']) ?></td>
      <td><?= e(mb_strimwidth($f['question'], 0, 100, '…')) ?></td>
      <td><span class="badge"><?= e($f['niveau_maturite']) ?></span></td>
      <td><?= e(substr((string) $f['date_presentation_min'], 0, 10)) ?></td>
      <td class="nowrap">
        <a class="btn small" href="index.php?page=fiches&action=form&id=<?= (int) $f['id'] ?>">Modifier</a>
        <form method="post" action="index.php?page=fiches&action=delete" class="inline" onsubmit="return confirm('Supprimer cette fiche ?')">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $f['id'] ?>"><button class="btn bad small">Supprimer</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$fiches): ?><tr><td colspan="5">Aucune fiche.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<?php if ($pages > 1):
    $q = static fn (int $p) => 'index.php?' . http_build_query(['page' => 'fiches', 'document_id' => $doc, 'niveau' => $niv, 'p' => $p]); ?>
<nav class="pager">
  <?php if ($page > 1): ?><a class="btn small" href="<?= e($q($page - 1)) ?>">« Précédent</a><?php endif; ?>
  <span>Page <?= (int) $page ?> / <?= (int) $pages ?></span>
  <?php if ($page < $pages): ?><a class="btn small" href="<?= e($q($page + 1)) ?>">Suivant »</a><?php endif; ?>
</nav>
<?php endif; ?>
