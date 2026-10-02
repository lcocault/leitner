<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> – Boîte de Leitner</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header>
  <strong class="brand">📚 Leitner</strong>
  <?php if ($logged): ?>
  <nav>
    <a href="index.php?page=revision">Réviser</a>
    <a href="index.php?page=dashboard">Progression</a>
    <a href="index.php?page=fiches">Fiches</a>
    <a href="index.php?page=documents">Documents</a>
    <a href="index.php?page=import">Import</a>
    <form method="post" action="index.php?page=logout" class="inline"><?= csrf_field() ?><button class="link">Déconnexion</button></form>
  </nav>
  <?php endif; ?>
</header>
<main>
<?php if (!empty($_SESSION['flash'])): [$ft, $fm] = $_SESSION['flash']; unset($_SESSION['flash']); ?>
  <p class="flash <?= $ft === 'err' ? 'err' : 'ok' ?>"><?= e($fm) ?></p>
<?php endif; ?>
<?= $content ?>
</main>
<script src="assets/app.js"></script>
</body>
</html>
