<h1>Connexion</h1>
<?php if ($error): ?><p class="flash err"><?= e($error) ?></p><?php endif; ?>
<form method="post" class="card">
  <?= csrf_field() ?>
  <label>Mot de passe <input type="password" name="password" required autofocus></label>
  <button class="btn">Se connecter</button>
</form>
