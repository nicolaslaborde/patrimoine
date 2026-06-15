<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/schema.php';

function renderHeader(string $title): void
{
    requireLogin();
    $nav = [
        ['Tableau de bord', 'dashboard.php'],
        ['Profil', 'form.php?rubrique=profil'],
        ['Revenus', 'rubrique.php?rubrique=revenus'],
        ['Dettes et crédits', 'rubrique.php?rubrique=dettesCredits'],
        ['Charges annuelles', 'rubrique.php?rubrique=chargesAnnuelles'],
        ['Immobilier', 'rubrique.php?rubrique=immobilier'],
        ['Mobilier et financier', 'rubrique.php?rubrique=mobilierFinancier'],
        ['Fiscalité', 'rubrique.php?rubrique=fiscalite'],
        ['Succession', 'rubrique.php?rubrique=successionTransmission'],
        ['Documents', 'rubrique.php?rubrique=documents'],
        ['Alertes', 'rubrique.php?rubrique=alertes'],
    ];
    if (isAdmin()) {
        $nav[] = ['Utilisateurs', 'utilisateurs.php'];
    }
    ?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> - <?= e(APP_NAME) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header class="topbar">
    <a class="brand" href="dashboard.php">Patrimoine</a>
    <nav>
      <?php foreach ($nav as [$label, $href]): ?>
        <a class="<?= currentPath() === basename(parse_url($href, PHP_URL_PATH) ?: '') ? 'active' : '' ?>" href="<?= e($href) ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
      <a class="button tiny danger logout-button" href="logout.php">Déconnexion</a>
    </nav>
  </header>
  <main class="page">
    <?php
}

function renderFooter(): void
{
    ?>
  </main>
  <script src="assets/js/app.js"></script>
  <script src="assets/js/forms.js"></script>
</body>
</html>
<?php
}

function pageH1(string $title): string
{
    return $title . ' - ' . currentPatrimoineOwner();
}

function euro($value): string
{
    return number_format((float)$value, 0, ',', ' ') . ' €';
}
