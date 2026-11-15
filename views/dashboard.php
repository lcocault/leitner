<h1>Progression</h1>
<p class="big"><?= e($pctM5) ?> % des fiches sont en M5 <span class="meta">(<?= (int) $colTotals['M5'] ?> / <?= (int) $total ?>)</span></p>
<div class="table-wrap">
<table class="matrix">
  <thead><tr><th>Document</th><?php foreach (Leitner::LEVELS as $l): ?><th><?= e($l) ?></th><?php endforeach; ?><th>Total</th></tr></thead>
  <tbody>
  <?php $courante = false; foreach ($rows as $r): ?>
    <?php if ($courante !== ($r['categorie'] ?? '')): $courante = $r['categorie'] ?? ''; ?>
      <tr class="categorie"><th colspan="<?= count(Leitner::LEVELS) + 2 ?>"><?= e($courante === '' ? 'Sans catégorie' : $courante) ?></th></tr>
    <?php endif; ?>
    <tr><th><?= e($r['titre']) ?></th>
      <?php foreach (Leitner::LEVELS as $l): ?><td><?= (int) $r['levels'][$l] ?></td><?php endforeach; ?>
      <td><strong><?= (int) $r['total'] ?></strong></td></tr>
  <?php endforeach; ?>
  </tbody>
  <tfoot><tr><th>Total</th><?php foreach (Leitner::LEVELS as $l): ?><td><?= (int) $colTotals[$l] ?></td><?php endforeach; ?><td><strong><?= (int) $total ?></strong></td></tr></tfoot>
</table>
</div>
