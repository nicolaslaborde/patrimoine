<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
ensureStorage();

$error = '';
if (isset($_GET['utilisateur'], $_GET['passwd'])) {
    if (loginUser(trim((string)$_GET['utilisateur']), (string)$_GET['passwd'])) {
        redirect('dashboard.php');
    }
    $error = 'Identifiants invalides.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (loginUser(trim((string)($_POST['username'] ?? '')), (string)($_POST['password'] ?? ''), isset($_POST['remember']))) {
        redirect('dashboard.php');
    }
    $error = 'Identifiants invalides.';
}
$remembered = $_COOKIE['patrimoine_username'] ?? INITIAL_USERNAME;
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Connexion - <?= e(APP_NAME) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
  <main class="login-panel compact-panel">
    <h1>Patrimoine</h1>
    <p class="muted">Connexion au dossier personnel</p>
    <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
    <form method="post" class="stack compact-form">
      <label>Utilisateur <input name="username" value="<?= e($remembered) ?>" autocomplete="username" required></label>
      <label>Mot de passe <input type="password" name="password" autocomplete="current-password" required></label>
      <label class="check"><input type="checkbox" name="remember" checked> Mémoriser l’identifiant</label>
      <button class="button primary">Se connecter</button>
    </form>
  </main>
</body>
</html>
