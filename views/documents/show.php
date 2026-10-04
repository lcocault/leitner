<?php
$url = 'index.php?page=documents&action=show&id=' . (int) $document['id'] . '&vue=';
$texte = $document[$vue];
$nb = count($chapitres);
?>
<p><a href="index.php?page=documents">← Documents</a></p>
<h1><?= e($document['titre']) ?></h1>
<p class="tabs">
  <a class="tab <?= $vue === 'resume' ? 'on' : '' ?>" href="<?= e($url . 'resume') ?>">Résumé</a>
  <a class="tab <?= $vue === 'cours' ? 'on' : '' ?>" href="<?= e($url . 'cours') ?>">Cours complet</a>
</p>
<?php if ($texte === null || $texte === ''): ?>
  <div class="card">
    <p>Aucun <?= $vue === 'cours' ? 'cours complet' : 'résumé' ?> pour ce document. Vous pouvez en <a href="index.php?page=import">importer un</a>.</p>
  </div>
<?php elseif ($vue === 'cours' && $nb > 0): ?>
  <div id="cours" data-document="<?= (int) $document['id'] ?>">
    <nav class="card" id="sommaire">
      <h2>Sommaire</h2>
      <p id="reprendre" hidden></p>
      <ol>
        <?php foreach ($chapitres as $i => $c): ?>
          <li><a href="#chapitre-<?= $i + 1 ?>"><?= e($c['titre']) ?></a></li>
        <?php endforeach; ?>
      </ol>
    </nav>
    <?php foreach ($chapitres as $i => $c): $n = $i + 1; ?>
      <section class="card chapitre" id="chapitre-<?= $n ?>" data-titre="<?= e($c['titre']) ?>">
        <p class="meta">Chapitre <?= $n ?> sur <?= $nb ?></p>
        <h2><?= e($c['titre']) ?> <button class="btn small speak" type="button" data-speak="#chapitre-<?= $n ?> .md" data-speak-title="<?= e($c['titre']) ?>" hidden>🔊 Écouter</button></h2>
        <div class="md"><?= Markdown::render($c['texte']) ?></div>
        <p class="chapnav">
          <?php if ($i > 0): ?><a class="btn small" href="#chapitre-<?= $n - 1 ?>">← Précédent</a><?php endif; ?>
          <a class="btn small" href="#sommaire">Sommaire</a>
          <?php if ($n < $nb): ?><a class="btn small" href="#chapitre-<?= $n + 1 ?>">Suivant : <?= e($chapitres[$i + 1]['titre']) ?> →</a><?php endif; ?>
        </p>
      </section>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="card">
    <button class="btn small speak" type="button" data-speak="#lecture" hidden>🔊 Écouter</button>
    <div class="md" id="lecture"><?= Markdown::render($texte) ?></div>
  </div>
<?php endif; ?>
<p><a class="btn" href="index.php?page=revision&document_id=<?= (int) $document['id'] ?>">Réviser ce document</a></p>
