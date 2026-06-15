<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
requireAdmin();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $user = createUser(
            (string)($_POST['username'] ?? ''),
            (string)($_POST['password'] ?? ''),
            (string)($_POST['role'] ?? 'user')
        );
        $message = 'Utilisateur créé : ' . $user['username'] . ' · fichier : data/' . $user['patrimoineFile'];
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$users = loadUsers();
renderHeader('Utilisateurs');
?>
<div class="page-head dense-head">
  <div>
    <h1><?= e(pageH1('Utilisateurs')) ?></h1>
    <p class="muted">Chaque utilisateur possède son propre fichier patrimoine JSON.</p>
  </div>
</div>

<?php if ($message): ?><p class="success-message"><?= e($message) ?></p><?php endif; ?>
<?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>

<section class="form-band">
  <h2>Créer un utilisateur</h2>
  <form method="post" class="form-grid">
    <label>Identifiant <span class="required-star">*</span>
      <input name="username" required pattern="[A-Za-z0-9_-]{3,40}" placeholder="prenom">
    </label>
    <label>Mot de passe <span class="required-star">*</span>
      <input type="password" name="password" required minlength="6">
    </label>
    <label>Rôle
      <select name="role">
        <option value="user">Utilisateur</option>
        <option value="admin">Administrateur</option>
      </select>
    </label>
    <div class="full actions">
      <button class="button primary">Créer</button>
    </div>
  </form>
</section>

<section class="table-section">
  <h2>Utilisateurs existants</h2>
  <div class="table-wrap">
    <table class="dense-table">
      <thead><tr><th>Identifiant</th><th>Rôle</th><th>Fichier patrimoine</th><th>Créé le</th></tr></thead>
      <tbody>
        <?php foreach ($users as $user): ?>
          <tr>
            <td><strong><?= e($user['username'] ?? '') ?></strong></td>
            <td><?= e($user['role'] ?? '') ?></td>
            <td><?= e('data/' . ($user['patrimoineFile'] ?? '')) ?></td>
            <td><?= e($user['createdAt'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php renderFooter(); ?>
