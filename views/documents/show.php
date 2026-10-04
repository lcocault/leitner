<p><a href="index.php?page=documents">← Documents</a></p>
<h1><?= e($document['titre']) ?></h1>
<div class="card">
  <h2>Résumé</h2>
  <?php if ($document['resume'] !== null && $document['resume'] !== ''): ?>
    <div class="md"><?= Markdown::render($document['resume']) ?></div>
  <?php else: ?>
    <p>Aucun résumé pour ce document. Vous pouvez en <a href="index.php?page=import">importer un</a>.</p>
  <?php endif; ?>
</div>
<p><a class="btn" href="index.php?page=revision&document_id=<?= (int) $document['id'] ?>">Réviser ce document</a></p>
