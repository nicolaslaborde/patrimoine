<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/calculations.php';

$data = loadPatrimoine();
$dashboard = calculateDashboard($data);
$schema = appSchema();

$liquidites = (float)$dashboard['liquiditesDisponibles'];
$mobilierNonLiquide = max(0, (float)$dashboard['mobilierFinancierTotal'] - $liquidites);
$patrimoineSlices = array_values(array_filter([
    ['label' => 'Immobilier', 'value' => (float)$dashboard['immobilierTotal'], 'color' => '#2563eb'],
    ['label' => 'Liquidités', 'value' => $liquidites, 'color' => '#16a34a'],
    ['label' => 'Produit financier', 'value' => $mobilierNonLiquide, 'color' => '#7c3aed'],
], fn(array $slice): bool => $slice['value'] > 0));
$patrimoineTotal = array_sum(array_column($patrimoineSlices, 'value'));
$pieGradient = pieGradient($patrimoineSlices, $patrimoineTotal);

$monthlyIncome = (float)$dashboard['revenusAnnuelsTotaux'] / 12;
$monthlyCharges = (float)$dashboard['chargesAnnuellesTotales'] / 12;
$monthlyAvailable = $monthlyIncome - $monthlyCharges;
$monthlySlices = $monthlyAvailable >= 0
    ? [
        ['label' => 'Charges mensuelles', 'value' => $monthlyCharges, 'color' => '#f59e0b'],
        ['label' => 'Disponible mensuel', 'value' => $monthlyAvailable, 'color' => '#2563eb'],
    ]
    : [
        ['label' => 'Revenus mensuels', 'value' => $monthlyIncome, 'color' => '#16a34a'],
        ['label' => 'Déficit mensuel', 'value' => abs($monthlyAvailable), 'color' => '#dc2626'],
    ];
$monthlySlices = array_values(array_filter($monthlySlices, fn(array $slice): bool => $slice['value'] > 0));
$monthlyTotal = array_sum(array_column($monthlySlices, 'value'));
$monthlyGradient = pieGradient($monthlySlices, $monthlyTotal);

$incomeBreakdown = dashboardIncomeBreakdown($data['revenus'] ?? []);
$cashflowRows = [
    ['label' => 'Revenus / salaire', 'annual' => $incomeBreakdown['salary'], 'href' => 'rubrique.php?rubrique=revenus', 'class' => 'green'],
    ['label' => 'Revenus immobilier', 'annual' => $incomeBreakdown['realEstate'], 'href' => 'rubrique.php?rubrique=revenus', 'class' => 'blue'],
    ['label' => 'Revenus financiers', 'annual' => $incomeBreakdown['financial'], 'href' => 'rubrique.php?rubrique=revenus', 'class' => 'violet'],
    ['label' => 'Dépenses', 'annual' => (float)$dashboard['chargesAnnuellesTotales'], 'href' => 'rubrique.php?rubrique=chargesAnnuelles', 'class' => 'amber'],
];
$maxCashflowAnnual = max(1.0, ...array_map(fn(array $row): float => abs((float)$row['annual']), $cashflowRows));
$immobilierEntries = $data['immobilier'] ?? [];

renderHeader('Tableau de bord');
?>
<div class="page-head dense-head">
  <div>
    <h1><?= e(pageH1('Tableau de bord')) ?></h1>
    <p class="muted">Dernière mise à jour : <?= e($dashboard['derniereMiseAJour']) ?></p>
  </div>
  <div class="actions">
    <a class="button primary" href="synthese.php">Synthèse patrimoine</a>
    <a class="button secondary" href="api/export-json.php">JSON</a>
    <a class="button secondary" href="api/export-csv.php">CSV</a>
  </div>
</div>

<section class="chart-view" aria-label="Vue graphiques du patrimoine">
  <article class="chart-panel chart-donut-panel">
    <div class="chart-title">
      <h2>Répartition brute</h2>
      <span><?= e(euro($patrimoineTotal)) ?></span>
    </div>
    <div class="donut-wrap">
      <div class="donut" style="--donut: conic-gradient(<?= e($pieGradient) ?>);">
        <span><?= $patrimoineTotal > 0 ? e(round(($dashboard['patrimoineNet'] / $patrimoineTotal) * 100)) : '0' ?>%</span>
        <small>net</small>
      </div>
      <ul class="chart-legend">
        <?php foreach ($patrimoineSlices as $slice): ?>
          <li>
            <span class="legend-dot" style="background: <?= e($slice['color']) ?>"></span>
            <span><?= e($slice['label']) ?></span>
            <strong><?= e(euro($slice['value'])) ?></strong>
          </li>
        <?php endforeach; ?>
        <?php if (!$patrimoineSlices): ?>
          <li><span class="legend-dot"></span><span>Aucune valeur saisie</span><strong>0 €</strong></li>
        <?php endif; ?>
      </ul>
    </div>
  </article>

  <article class="chart-panel chart-donut-panel">
    <div class="chart-title">
      <h2>Flux mensuels</h2>
      <span><?= e(euro($monthlyIncome)) ?> revenus/mois</span>
    </div>
    <div class="donut-wrap">
      <div class="donut" style="--donut: conic-gradient(<?= e($monthlyGradient) ?>);">
        <span><?= e(euro($monthlyIncome)) ?></span>
        <small>revenus/mois</small>
      </div>
      <ul class="chart-legend">
        <?php foreach ($monthlySlices as $slice): ?>
          <li>
            <span class="legend-dot" style="background: <?= e($slice['color']) ?>"></span>
            <span><?= e($slice['label']) ?></span>
            <strong><?= e(euro($slice['value'])) ?></strong>
          </li>
        <?php endforeach; ?>
        <?php if (!$monthlySlices): ?>
          <li><span class="legend-dot"></span><span>Aucun flux saisi</span><strong>0 €</strong></li>
        <?php endif; ?>
      </ul>
    </div>
  </article>
</section>

<section class="table-section cashflow-panel">
  <h2>Flux revenus / dépenses</h2>
  <div class="table-wrap">
    <table class="dense-table dashboard-cashflow-table">
      <thead><tr><th>Poste</th><th>Mensuel</th><th>Annuel</th><th>Proportion</th></tr></thead>
      <tbody>
      <?php foreach ($cashflowRows as $row): ?>
        <?php $width = round((abs((float)$row['annual']) / $maxCashflowAnnual) * 100, 1); ?>
        <tr>
          <td><a class="dashboard-row-link" href="<?= e($row['href']) ?>"><?= e($row['label']) ?></a></td>
          <td><?= e(euro((float)$row['annual'] / 12)) ?></td>
          <td><?= e(euro((float)$row['annual'])) ?></td>
          <td>
            <div class="proportion-track" aria-label="<?= e($width) ?>%">
              <span class="proportion-fill <?= e($row['class']) ?>" style="width: <?= e($width) ?>%"></span>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="real-estate-panel">
  <div class="section-title-row">
    <h2>Immobilier</h2>
    <a class="button tiny secondary" href="rubrique.php?rubrique=immobilier">Voir la rubrique</a>
  </div>
  <div class="property-grid">
    <?php if (!$immobilierEntries): ?>
      <p class="muted">Aucun bien immobilier saisi.</p>
    <?php endif; ?>
    <?php foreach ($immobilierEntries as $entry): ?>
      <?php
      $type = (string)($entry['type'] ?? '');
      $niveau1 = $entry['niveau1'] ?? [];
      $location = trim((string)($niveau1['adresse'] ?? ''));
      $title = trim((string)($entry['titre'] ?? 'Bien immobilier'));
      $typeBien = (string)($niveau1['typeBien'] ?? '');
      $image = propertyImageFor($typeBien, $type, $title);
      ?>
      <a class="property-tile" href="form.php?rubrique=immobilier&id=<?= e($entry['id'] ?? '') ?>">
        <img class="property-image" src="<?= e($image) ?>" alt="">
        <strong><?= e($title) ?></strong>
        <span><?= e($location !== '' ? $location : $type) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="card-grid dense-cards">
  <?php foreach ($dashboard['rubriques'] as $rubrique): ?>
    <?php $key = $rubrique['key']; $isProfil = $key === 'profil'; ?>
    <article class="rubrique-card">
      <div class="card-title">
        <h2><?= e($rubrique['label']) ?></h2>
        <a class="pill success" href="<?= $isProfil ? 'form.php?rubrique=profil' : 'form.php?rubrique=' . e($key) ?>"><?= e($rubrique['count']) ?></a>
        <a class="pill danger" href="<?= $isProfil ? 'form.php?rubrique=profil' : 'rubrique.php?rubrique=' . e($key) ?>"><?= e($rubrique['missing']) ?></a>
        <button class="mini info-toggle" data-info="<?= e($key) ?>">?</button>
      </div>
      <dl class="mini-stats">
        <dt>Valeur</dt><dd><?= e(euro((float)$rubrique['value'])) ?></dd>
        <dt>Revenus</dt><dd><?= e(euro((float)$rubrique['revenus'])) ?></dd>
        <dt>Charges</dt><dd><?= e(euro((float)$rubrique['charges'])) ?></dd>
      </dl>
      <div class="actions tight">
        <?php if (!$isProfil): ?><a class="button tiny secondary" href="rubrique.php?rubrique=<?= e($key) ?>">Voir</a><?php endif; ?>
        <a class="button tiny primary" href="form.php?rubrique=<?= e($key) ?>">Ajouter</a>
      </div>
      <div class="info-pop" id="info-<?= e($key) ?>" hidden>
        <strong>Attendus niveau 1</strong>
        <p><?= e(implode(', ', $schema[$key]['niveau1Required'] ?? [])) ?></p>
        <strong>Types</strong>
        <p><?= e(implode(', ', $schema[$key]['types'] ?? [])) ?></p>
      </div>
    </article>
  <?php endforeach; ?>
</section>
<?php renderFooter(); ?>

<?php
function pieGradient(array $slices, float $total): string
{
    $cursor = 0.0;
    $segments = [];
    foreach ($slices as $slice) {
        $start = $cursor;
        $cursor += $total > 0 ? ($slice['value'] / $total) * 100 : 0;
        $segments[] = $slice['color'] . ' ' . round($start, 2) . '% ' . round($cursor, 2) . '%';
    }
    return $segments ? implode(', ', $segments) : '#e5e7eb 0 100%';
}

function dashboardIncomeBreakdown(array $revenus): array
{
    $breakdown = ['salary' => 0.0, 'realEstate' => 0.0, 'financial' => 0.0];
    foreach ($revenus as $revenu) {
        $niveau1 = $revenu['niveau1'] ?? [];
        $annual = num($niveau1['montantAnnuel'] ?? 0);
        $type = mb_strtolower((string)($revenu['type'] ?? ''), 'UTF-8');
        $title = mb_strtolower((string)($revenu['titre'] ?? ''), 'UTF-8');
        $payer = mb_strtolower((string)($niveau1['organismePayeur'] ?? ''), 'UTF-8');
        $source = (string)($revenu['sourceAutomatique'] ?? '');
        $text = $type . ' ' . $title . ' ' . $payer;

        if ($source === 'immobilierRegimeFiscal' || str_contains($text, 'immobilier') || str_contains($text, 'foncier')) {
            $breakdown['realEstate'] += $annual;
        } elseif (str_contains($text, 'financier') || str_contains($text, 'dividende') || str_contains($text, 'interet') || str_contains($text, 'intérêt') || str_contains($text, 'scpi') || str_contains($text, 'placement')) {
            $breakdown['financial'] += $annual;
        } else {
            $breakdown['salary'] += $annual;
        }
    }
    return $breakdown;
}

function propertyImageFor(string $typeBien, string $type, string $title): string
{
    $selected = mb_strtolower($typeBien, 'UTF-8');
    $basePath = 'assets/img/immobilier/';
    if ($selected === 'maison') {
        return $basePath . 'maison.png';
    }
    if ($selected === 'appartement') {
        return $basePath . 'appartement.png';
    }
    if ($selected === 'immeuble') {
        return $basePath . 'immeuble.png';
    }
    if ($selected === 'terrain') {
        return $basePath . 'terrain.png';
    }
    if ($selected === 'parking') {
        return $basePath . 'parking.png';
    }
    if ($selected === 'parts de sci immobilière' || $selected === 'parts de sci') {
        return $basePath . 'parts-sci.png';
    }
    if ($selected === 'autre') {
        return $basePath . 'autre.png';
    }

    $text = mb_strtolower($type . ' ' . $title, 'UTF-8');
    if (str_contains($text, 'terrain')) {
        return $basePath . 'terrain.png';
    }
    if (str_contains($text, 'parking') || str_contains($text, 'garage') || str_contains($text, 'box')) {
        return $basePath . 'parking.png';
    }
    if (str_contains($text, 'sci')) {
        return $basePath . 'parts-sci.png';
    }
    if (str_contains($text, 'appartement') || str_contains($text, 'studio')) {
        return $basePath . 'appartement.png';
    }
    if (str_contains($text, 'immeuble') || str_contains($text, 'locatif') || str_contains($text, 'commercial')) {
        return $basePath . 'immeuble.png';
    }
    if (str_contains($text, 'autre')) {
        return $basePath . 'autre.png';
    }
    return $basePath . 'maison.png';
}
