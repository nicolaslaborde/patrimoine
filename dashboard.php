<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/calculations.php';

$data = loadPatrimoine();
$dashboard = calculateDashboard($data);
$missing = getMissingInformation($data);
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

<section class="metrics compact-grid">
  <?php
  $metrics = [
      'Patrimoine brut' => $dashboard['patrimoineBrutTotal'],
      'Dettes' => $dashboard['dettesTotales'],
      'Patrimoine net' => $dashboard['patrimoineNet'],
      'Immobilier' => $dashboard['immobilierTotal'],
      'Mobilier/financier' => $dashboard['mobilierFinancierTotal'],
      'Liquidités' => $dashboard['liquiditesDisponibles'],
      'Revenus/an' => $dashboard['revenusAnnuelsTotaux'],
      'Charges/an' => $dashboard['chargesAnnuellesTotales'],
      'Net estimé/an' => $dashboard['revenuNetAnnuelEstime'],
      'Manquants' => $dashboard['nombreInformationsManquantes'],
  ];
  foreach ($metrics as $label => $value): ?>
    <?php if ($label === 'Liquidités'): ?>
      <a class="metric metric-link" href="liquidites.php"><span><?= e($label) ?></span><strong><?= e(euro((float)$value)) ?></strong></a>
    <?php else: ?>
      <article class="metric"><span><?= e($label) ?></span><strong><?= is_numeric($value) && $label !== 'Manquants' ? e(euro((float)$value)) : e($value) ?></strong></article>
    <?php endif; ?>
  <?php endforeach; ?>
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

<section class="table-section">
  <h2>Informations manquantes niveau 1</h2>
  <div class="table-wrap">
    <table class="dense-table">
      <thead><tr><th>Rubrique</th><th>Fiche</th><th>Champ</th><th>Importance</th><th></th></tr></thead>
      <tbody>
      <?php if (!$missing): ?>
        <tr><td colspan="5">Aucune information manquante.</td></tr>
      <?php endif; ?>
      <?php foreach ($missing as $item): ?>
        <tr>
          <td><?= e($schema[$item['rubrique']]['label'] ?? $item['rubrique']) ?></td>
          <td><?= e($item['entryLabel']) ?></td>
          <td><?= e($item['field']) ?></td>
          <td><span class="badge <?= e($item['importance']) ?>"><?= e($item['importance']) ?></span></td>
          <td><a class="button tiny secondary" href="<?= $item['rubrique'] === 'profil' ? 'form.php?rubrique=profil' : 'form.php?rubrique=' . e($item['rubrique']) . '&id=' . e($item['entryId']) ?>">Modifier</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
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
