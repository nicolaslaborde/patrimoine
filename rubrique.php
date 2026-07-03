<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/calculations.php';
require_once __DIR__ . '/includes/immobilier-revenus.php';

$rubrique = $_GET['rubrique'] ?? '';
if (!in_array($rubrique, array_slice(RUBRIQUES, 1), true)) {
    redirect('dashboard.php');
}
$schema = schemaFor($rubrique);
$data = loadPatrimoine();
if ($rubrique === 'revenus') {
    $beforeSync = $data['revenus'] ?? [];
    syncImmobilierRegimeFiscalRevenus($data);
    if ($beforeSync !== ($data['revenus'] ?? [])) {
        savePatrimoine($data);
    }
}
$entries = $data[$rubrique] ?? [];
$revenusParBien = $rubrique === 'immobilier' ? revenusByImmobilier($data['revenus'] ?? []) : [];
$totals = ['value' => 0.0, 'globalValue' => 0.0, 'quotePartValue' => 0.0, 'revenue' => 0.0, 'netRevenue' => 0.0, 'charge' => 0.0, 'debt' => 0.0];
foreach ($entries as $entry) {
    $n1 = $entry['niveau1'] ?? [];
    $metrics = rowMetrics($rubrique, $n1, $entry, $revenusParBien);
    $totals['value'] += $metrics['value'];
    $totals['globalValue'] += $rubrique === 'immobilier' ? num($n1['valeurActuelle'] ?? 0) : 0.0;
    $totals['quotePartValue'] += $rubrique === 'immobilier' ? quotePartValue($n1) : 0.0;
    $totals['revenue'] += $metrics['revenue'];
    $totals['netRevenue'] += $metrics['netRevenue'];
    $totals['charge'] += $metrics['charge'];
    $totals['debt'] += $metrics['debt'];
}
$printSorts = [
    'chargesAnnuelles' => ['column' => 4, 'type' => 'number', 'direction' => 'desc'],
    'immobilier' => ['column' => 3, 'type' => 'number', 'direction' => 'desc'],
    'revenus' => ['column' => 2, 'type' => 'number', 'direction' => 'desc'],
    'mobilierFinancier' => ['column' => 2, 'type' => 'number', 'direction' => 'desc'],
];
$printSort = $printSorts[$rubrique] ?? ['column' => 0, 'type' => 'text', 'direction' => 'asc'];
$tableId = $rubrique === 'chargesAnnuelles'
    ? 'charges-table'
    : ($rubrique === 'mobilierFinancier' ? 'mobilier-financier-table' : 'rubrique-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $rubrique) . '-table');
$chargesByType = $rubrique === 'chargesAnnuelles' ? chargePieData($entries, 'type') : null;
$chargesByCategory = $rubrique === 'chargesAnnuelles' ? chargePieData($entries, 'categorieDetaillee') : null;
$chargeCategoryOptions = $rubrique === 'chargesAnnuelles' ? (($schema['niveau1']['categorieDetaillee']['options'] ?? [])) : [];
$revenusByType = $rubrique === 'revenus' ? revenuePieData($entries) : null;
$immobilierUsufruitByType = $rubrique === 'immobilier' ? immobilierUsufruitPieData($entries) : null;
$immobilierQuotePartByTitle = $rubrique === 'immobilier' ? immobilierQuotePartByTitlePieData($entries) : null;
$mobilierValueByType = $rubrique === 'mobilierFinancier' ? mobilierValuePieData($entries) : null;
renderHeader($schema['label']);
?>
<div class="page-head dense-head">
  <div>
    <h1><?= e(pageH1($schema['label'])) ?></h1>
    <p class="muted"><?= count($entries) ?> fiche(s)</p>
  </div>
  <div class="actions">
    <button class="button secondary" type="button" id="printRubrique">Imprimer <?= e($schema['label']) ?></button>
    <a class="button primary" href="form.php?rubrique=<?= e($rubrique) ?>">Ajouter</a>
  </div>
</div>

<?php if ($rubrique === 'chargesAnnuelles'): ?>
  <section class="chart-view charges-chart-view" aria-label="Répartition des charges annuelles">
    <?= renderFinancePie('Charges par type', $chargesByType, 'Aucune charge saisie') ?>
    <?= renderFinancePie('Charges par catégorie', $chargesByCategory, 'Aucune charge saisie') ?>
  </section>
<?php endif; ?>

<?php if ($rubrique === 'revenus'): ?>
  <section class="chart-view single-chart-view" aria-label="Répartition des revenus annuels">
    <?= renderFinancePie('Revenus par type', $revenusByType, 'Aucun revenu saisi') ?>
  </section>
<?php endif; ?>

<?php if ($rubrique === 'immobilier'): ?>
  <section class="chart-view" aria-label="Répartition des valeurs immobilières">
    <?= renderFinancePie('Usufruit par type', $immobilierUsufruitByType, 'Aucun bien saisi', $immobilierUsufruitByType['total'] ?? 0, 'usufruit') ?>
    <?= renderFinancePie('% PP par titre', $immobilierQuotePartByTitle, 'Aucun bien saisi', $immobilierQuotePartByTitle['total'] ?? 0, '% PP') ?>
  </section>
<?php endif; ?>

<?php if ($rubrique === 'mobilierFinancier'): ?>
  <section class="chart-view single-chart-view" aria-label="Répartition de la valeur mobilier et financier">
    <?= renderFinancePie('Valeur par type', $mobilierValueByType, 'Aucune valeur saisie', $mobilierValueByType['total'] ?? 0, 'valeur') ?>
  </section>
<?php endif; ?>

<?php if ($rubrique === 'chargesAnnuelles'): ?>
  <div class="table-controls" aria-label="Filtres charges annuelles">
    <label class="table-filter-field">
      <span>Catégorie</span>
      <select
        class="table-filter"
        data-filter-table="<?= e($tableId) ?>"
        data-filter-key="categorie"
        data-filter-row-key="category"
        data-filter-scope="rubrique-chargesAnnuelles"
      >
        <option value="">Toutes les catégories</option>
        <?php foreach ($chargeCategoryOptions as $category): ?>
          <option value="<?= e($category) ?>"><?= e($category) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </div>
<?php endif; ?>

<div class="table-wrap">
  <table id="<?= e($tableId) ?>" class="dense-table print-rubrique-table" data-print-sort-column="<?= e($printSort['column']) ?>" data-print-sort-type="<?= e($printSort['type']) ?>" data-print-sort-direction="<?= e($printSort['direction']) ?>">
    <?php if ($rubrique === 'chargesAnnuelles'): ?>
    <thead>
      <tr>
        <th><?= sortHeader($tableId, 0, 'text', 'Type') ?></th><th><?= sortHeader($tableId, 1, 'text', 'Titre') ?></th><th><?= sortHeader($tableId, 2, 'text', 'Catégorie') ?></th><th><?= sortHeader($tableId, 3, 'text', 'Bien concerné') ?></th><th><?= sortHeader($tableId, 4, 'number', 'Coût/mois') ?></th><th><?= sortHeader($tableId, 5, 'number', 'Coût/an') ?></th><th class="print-hidden">Liens</th><th><?= sortHeader($tableId, 7, 'text', 'Commentaire') ?></th><th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$entries): ?><tr><td colspan="9">Aucune fiche.</td></tr><?php endif; ?>
      <?php foreach ($entries as $entry): ?>
        <?php $n1 = $entry['niveau1'] ?? []; ?>
        <tr
          data-filter-row
          data-filter-category="<?= e($n1['categorieDetaillee'] ?? '') ?>"
          data-monthly-value="<?= e(num($n1['montantMensuel'] ?? 0)) ?>"
          data-annual-value="<?= e(num($n1['montantAnnuel'] ?? 0)) ?>"
        >
          <td data-sort-value="<?= e($entry['type'] ?? '') ?>"><?= e($entry['type'] ?? '') ?></td>
          <td data-sort-value="<?= e($entry['titre'] ?? '') ?>"><strong><?= e($entry['titre'] ?? '') ?></strong></td>
          <td data-sort-value="<?= e($n1['categorieDetaillee'] ?? '') ?>"><?= e($n1['categorieDetaillee'] ?? '') ?></td>
          <td data-sort-value="<?= e($n1['bienConcerne'] ?? '') ?>"><?= e($n1['bienConcerne'] ?? '') ?></td>
          <td data-sort-value="<?= e(num($n1['montantMensuel'] ?? 0)) ?>"><?= e(euro(num($n1['montantMensuel'] ?? 0))) ?></td>
          <td data-sort-value="<?= e(num($n1['montantAnnuel'] ?? 0)) ?>"><?= e(euro(num($n1['montantAnnuel'] ?? 0))) ?></td>
          <td class="print-hidden">
            <?php foreach (($entry['liens'] ?? []) as $link): ?>
              <a class="link-chip" href="<?= e($link['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($link['description'] ?: 'Lien') ?></a>
            <?php endforeach; ?>
          </td>
          <td data-sort-value="<?= e($entry['commentaire'] ?? '') ?>"><?= e($entry['commentaire'] ?? '') ?></td>
          <td class="row-actions">
            <?php $isAutoImmobilierRevenu = $rubrique === 'revenus' && (($entry['sourceAutomatique'] ?? '') === 'immobilierRegimeFiscal'); ?>
            <a class="button tiny secondary" href="form.php?rubrique=<?= e($rubrique) ?>&id=<?= e($entry['id']) ?>"><?= $isAutoImmobilierRevenu ? 'Voir' : 'Modifier' ?></a>
            <?php if (!$isAutoImmobilierRevenu): ?>
              <button class="button tiny danger js-delete" data-rubrique="<?= e($rubrique) ?>" data-id="<?= e($entry['id']) ?>">Supprimer</button>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if ($entries): ?><tr class="filter-empty-row" hidden><td colspan="9">Aucune fiche pour ce filtre.</td></tr><?php endif; ?>
    </tbody>
    <tfoot>
      <tr class="total-row">
        <td colspan="4">Total</td>
        <td data-filter-total="monthly"><?= e(euro(sumChargesMonthly($entries))) ?></td>
        <td data-filter-total="annual"><?= e(euro($totals['charge'])) ?></td>
        <td class="print-hidden"></td>
        <td></td>
        <td class="print-hidden"></td>
      </tr>
    </tfoot>
    <?php else: ?>
    <thead>
      <tr>
        <?php if ($rubrique === 'immobilier'): ?>
          <th><?= sortHeader($tableId, 0, 'text', 'Type') ?></th><th><?= sortHeader($tableId, 1, 'text', 'Titre') ?></th><th><?= sortHeader($tableId, 2, 'number', 'Valeur globale') ?></th><th><?= sortHeader($tableId, 3, 'number', '% de PP') ?></th><th><?= sortHeader($tableId, 4, 'number', 'Revenu brut global/an') ?></th><th><?= sortHeader($tableId, 5, 'number', 'Revenu net/an') ?></th><th><?= sortHeader($tableId, 6, 'number', 'Charge/an') ?></th><th class="print-hidden">Liens</th><th class="print-hidden">Infos</th><th></th>
        <?php elseif ($rubrique === 'revenus'): ?>
          <th><?= sortHeader($tableId, 0, 'text', 'Type') ?></th><th><?= sortHeader($tableId, 1, 'text', 'Titre') ?></th><th><?= sortHeader($tableId, 2, 'number', 'Revenu/mois') ?></th><th><?= sortHeader($tableId, 3, 'number', 'Revenu/an') ?></th><th class="print-hidden">Liens</th><th><?= sortHeader($tableId, 5, 'text', 'Commentaire') ?></th><th></th>
        <?php elseif ($rubrique === 'mobilierFinancier'): ?>
          <th><?= sortHeader($tableId, 0, 'text', 'Type') ?></th><th><?= sortHeader($tableId, 1, 'text', 'Titre') ?></th><th><?= sortHeader($tableId, 2, 'number', 'Valeur') ?></th><th><?= sortHeader($tableId, 3, 'text', 'Disponibilité') ?></th><th class="print-hidden">Liens</th><th><?= sortHeader($tableId, 5, 'text', 'Commentaire') ?></th><th></th>
        <?php else: ?>
          <th><?= sortHeader($tableId, 0, 'text', 'Type') ?></th><th><?= sortHeader($tableId, 1, 'text', 'Titre') ?></th><th><?= sortHeader($tableId, 2, 'number', 'Valeur') ?></th><th><?= sortHeader($tableId, 3, 'number', 'Revenu/an') ?></th><th><?= sortHeader($tableId, 4, 'number', 'Charge/an') ?></th><th><?= sortHeader($tableId, 5, 'number', 'Dette') ?></th><th class="print-hidden">Liens</th><th><?= sortHeader($tableId, 7, 'text', 'Commentaire') ?></th><th></th>
        <?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php $emptyColspan = $rubrique === 'immobilier' ? 10 : ($rubrique === 'revenus' ? 7 : ($rubrique === 'mobilierFinancier' ? 7 : 9)); ?>
      <?php if (!$entries): ?><tr><td colspan="<?= e($emptyColspan) ?>">Aucune fiche.</td></tr><?php endif; ?>
      <?php foreach ($entries as $entry): ?>
        <?php $n1 = $entry['niveau1'] ?? []; $metrics = rowMetrics($rubrique, $n1, $entry, $revenusParBien); ?>
        <tr>
          <td data-sort-value="<?= e($entry['type'] ?? '') ?>"><?= e($entry['type'] ?? '') ?></td>
          <td data-sort-value="<?= e($entry['titre'] ?? '') ?>"><strong><?= e($entry['titre'] ?? '') ?></strong></td>
          <?php if ($rubrique !== 'revenus'): ?>
            <?php $displayValue = $rubrique === 'immobilier' ? num($n1['valeurActuelle'] ?? 0) : $metrics['value']; ?>
            <td data-sort-value="<?= e($displayValue) ?>"><?= e(euro($displayValue)) ?></td>
          <?php endif; ?>
          <?php if ($rubrique === 'immobilier'): ?>
            <?php $quotePartDisplayPercent = quotePartFactor($n1) * 100; ?>
            <td data-sort-value="<?= e($quotePartDisplayPercent) ?>"><?= e(formatPercentFactor(quotePartFactor($n1))) ?></td>
          <?php endif; ?>
          <?php if ($rubrique === 'revenus'): ?>
            <?php $monthlyRevenue = num($n1['montantMensuel'] ?? (($metrics['revenue'] ?? 0) / 12)); ?>
            <td data-sort-value="<?= e($monthlyRevenue) ?>"><?= e(euro($monthlyRevenue)) ?></td>
          <?php endif; ?>
          <?php if ($rubrique === 'mobilierFinancier'): ?>
            <?php $disponibilite = (string)($n1['liquidite'] ?? (($entry['niveau2'] ?? [])['disponibiliteFonds'] ?? '')); ?>
            <td data-sort-value="<?= e($disponibilite) ?>"><?= e($disponibilite) ?></td>
          <?php endif; ?>
          <?php if ($rubrique !== 'mobilierFinancier'): ?>
            <td data-sort-value="<?= e($metrics['revenue']) ?>"><?= e(euro($metrics['revenue'])) ?></td>
            <?php if ($rubrique === 'immobilier'): ?>
              <td data-sort-value="<?= e($metrics['netRevenue']) ?>"><?= e(euro($metrics['netRevenue'])) ?></td>
            <?php endif; ?>
            <?php if ($rubrique !== 'revenus'): ?>
              <td data-sort-value="<?= e($metrics['charge']) ?>"><?= e(euro($metrics['charge'])) ?></td>
              <?php if ($rubrique !== 'immobilier'): ?>
                <td data-sort-value="<?= e($metrics['debt']) ?>"><?= e(euro($metrics['debt'])) ?></td>
              <?php endif; ?>
            <?php endif; ?>
          <?php endif; ?>
          <td class="print-hidden">
            <?php if ($rubrique === 'immobilier' && trim((string)($n1['lienWeb'] ?? '')) !== ''): ?>
              <a class="link-chip" href="<?= e($n1['lienWeb']) ?>" target="_blank" rel="noopener noreferrer">Lien web</a>
            <?php else: ?>
              <?php foreach (($entry['liens'] ?? []) as $link): ?>
                <a class="link-chip" href="<?= e($link['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($link['description'] ?: 'Lien') ?></a>
              <?php endforeach; ?>
            <?php endif; ?>
          </td>
          <?php if ($rubrique === 'immobilier'): ?>
            <td class="print-hidden"><?= renderImmobilierInfoChips($entry) ?></td>
          <?php else: ?>
            <td data-sort-value="<?= e($entry['commentaire'] ?? '') ?>"><?= e($entry['commentaire'] ?? '') ?></td>
          <?php endif; ?>
          <td class="row-actions">
            <a class="button tiny secondary" href="form.php?rubrique=<?= e($rubrique) ?>&id=<?= e($entry['id']) ?>">Modifier</a>
            <button class="button tiny danger js-delete" data-rubrique="<?= e($rubrique) ?>" data-id="<?= e($entry['id']) ?>">Supprimer</button>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr class="total-row">
        <td colspan="2">Total</td>
        <?php if ($rubrique !== 'revenus'): ?>
          <td><?= e(euro($rubrique === 'immobilier' ? $totals['globalValue'] : $totals['value'])) ?></td>
        <?php endif; ?>
        <?php if ($rubrique === 'immobilier'): ?>
          <td></td>
        <?php endif; ?>
        <?php if ($rubrique === 'revenus'): ?>
          <td><?= e(euro($totals['revenue'] / 12)) ?></td>
        <?php endif; ?>
        <?php if ($rubrique !== 'mobilierFinancier'): ?>
          <td><?= e(euro($totals['revenue'])) ?></td>
          <?php if ($rubrique === 'immobilier'): ?>
            <td><?= e(euro($totals['netRevenue'])) ?></td>
          <?php endif; ?>
          <?php if ($rubrique !== 'revenus'): ?>
            <td><?= e(euro($totals['charge'])) ?></td>
            <?php if ($rubrique !== 'immobilier'): ?>
              <td><?= e(euro($totals['debt'])) ?></td>
            <?php endif; ?>
          <?php endif; ?>
        <?php endif; ?>
        <?php if ($rubrique === 'mobilierFinancier'): ?>
          <td></td>
        <?php endif; ?>
        <td class="print-hidden"></td>
        <?php if ($rubrique === 'immobilier'): ?>
          <td class="print-hidden"></td>
        <?php else: ?>
          <td></td>
        <?php endif; ?>
        <td class="print-hidden"></td>
      </tr>
    </tfoot>
    <?php endif; ?>
  </table>
</div>
<?php renderFooter(); ?>

<?php
function sumChargesMonthly(array $entries): float
{
    return array_reduce($entries, fn(float $sum, array $entry): float => $sum + num(($entry['niveau1'] ?? [])['montantMensuel'] ?? 0), 0.0);
}

function sortHeader(string $tableId, int $column, string $type, string $label): string
{
    return '<button class="table-sort" type="button" data-sort-table="' . e($tableId) . '" data-sort-column="' . e($column) . '" data-sort-type="' . e($type) . '">' . e($label) . '</button>';
}

function chargePieData(array $entries, string $field): array
{
    return pieDataFromEntries($entries, function (array $entry) use ($field): string {
        $n1 = $entry['niveau1'] ?? [];
        return $field === 'type' ? (string)($entry['type'] ?? '') : (string)($n1[$field] ?? '');
    }, fn(array $entry): float => num(($entry['niveau1'] ?? [])['montantAnnuel'] ?? 0));
}

function revenuePieData(array $entries): array
{
    return pieDataFromEntries($entries, fn(array $entry): string => (string)($entry['type'] ?? ''), fn(array $entry): float => num(($entry['niveau1'] ?? [])['montantAnnuel'] ?? 0));
}

function immobilierUsufruitPieData(array $entries): array
{
    return pieDataFromEntries($entries, fn(array $entry): string => (string)($entry['type'] ?? ''), fn(array $entry): float => usufruitFiscalValueUnder70($entry['niveau1'] ?? []));
}

function immobilierQuotePartByTitlePieData(array $entries): array
{
    return pieDataFromEntries($entries, fn(array $entry): string => (string)($entry['titre'] ?? ''), fn(array $entry): float => quotePartValue($entry['niveau1'] ?? []));
}

function mobilierValuePieData(array $entries): array
{
    return pieDataFromEntries($entries, fn(array $entry): string => (string)($entry['type'] ?? ''), fn(array $entry): float => patrimonialValue($entry['niveau1'] ?? [], 'mobilierFinancier'));
}

function pieDataFromEntries(array $entries, callable $labelForEntry, callable $valueForEntry): array
{
    $colors = ['#2563eb', '#16a34a', '#f59e0b', '#dc2626', '#7c3aed', '#0891b2', '#db2777', '#65a30d', '#9333ea', '#475569'];
    $groups = [];
    foreach ($entries as $entry) {
        $label = trim((string)$labelForEntry($entry));
        $label = $label !== '' ? $label : 'Non renseigné';
        $value = (float)$valueForEntry($entry);
        if ($value <= 0) {
            continue;
        }
        $groups[$label] = ($groups[$label] ?? 0) + $value;
    }
    arsort($groups);
    $total = array_sum($groups);
    $cursor = 0.0;
    $slices = [];
    $segments = [];
    $index = 0;
    foreach ($groups as $label => $value) {
        $color = $colors[$index % count($colors)];
        $start = $cursor;
        $cursor += $total > 0 ? ($value / $total) * 100 : 0;
        $segments[] = $color . ' ' . round($start, 2) . '% ' . round($cursor, 2) . '%';
        $slices[] = ['label' => $label, 'value' => $value, 'color' => $color];
        $index++;
    }
    return [
        'total' => $total,
        'slices' => $slices,
        'gradient' => $segments ? implode(', ', $segments) : '#e5e7eb 0 100%',
    ];
}

function renderFinancePie(string $title, ?array $data, string $emptyLabel, ?float $centerValue = null, string $centerLabel = '/mois'): string
{
    $data ??= ['total' => 0, 'slices' => [], 'gradient' => '#e5e7eb 0 100%'];
    $centerValue ??= ((float)$data['total']) / 12;
    ob_start();
    ?>
    <article class="chart-panel chart-donut-panel">
      <div class="chart-title">
        <h2><?= e($title) ?></h2>
        <span><?= e(euro($data['total'])) ?></span>
      </div>
      <div class="donut-wrap">
        <div class="donut" style="--donut: conic-gradient(<?= e($data['gradient']) ?>);">
          <span><?= e(euro($centerValue)) ?></span>
          <small><?= e($centerLabel) ?></small>
        </div>
        <ul class="chart-legend">
          <?php foreach (array_slice($data['slices'], 0, 8) as $slice): ?>
            <li>
              <span class="legend-dot" style="background: <?= e($slice['color']) ?>"></span>
              <span><?= e($slice['label']) ?></span>
              <strong><?= e(euro($slice['value'])) ?></strong>
            </li>
          <?php endforeach; ?>
          <?php if (!$data['slices']): ?>
            <li><span class="legend-dot"></span><span><?= e($emptyLabel) ?></span><strong>0 €</strong></li>
          <?php endif; ?>
        </ul>
      </div>
    </article>
    <?php
    return ob_get_clean();
}

function renderImmobilierInfoChips(array $entry): string
{
    $chips = [];
    $id = (string)($entry['id'] ?? '');
    $formUrl = 'form.php?rubrique=immobilier&id=' . rawurlencode($id);
    $n2 = $entry['niveau2'] ?? [];
    $commentaire = trim((string)($entry['commentaire'] ?? ''));
    if ($commentaire !== '') {
        $chips[] = '<a class="link-chip" href="' . e($formUrl) . '" title="' . e($commentaire) . '">Commentaire</a>';
    }
    $gestionnaire = trim((string)($n2['administrateurBien'] ?? ''));
    if ($gestionnaire !== '') {
        $url = trim((string)($n2['administrateurBienUrl'] ?? ''));
        $href = $url !== '' ? $url : $formUrl;
        $target = $url !== '' ? ' target="_blank" rel="noopener noreferrer"' : '';
        $chips[] = '<a class="link-chip" href="' . e($href) . '"' . $target . ' title="' . e($gestionnaire) . '">Gestion</a>';
    }
    $syndic = trim((string)($n2['syndicBien'] ?? ''));
    if ($syndic !== '') {
        $url = trim((string)($n2['syndicBienUrl'] ?? ''));
        $href = $url !== '' ? $url : $formUrl;
        $target = $url !== '' ? ' target="_blank" rel="noopener noreferrer"' : '';
        $chips[] = '<a class="link-chip" href="' . e($href) . '"' . $target . ' title="' . e($syndic) . '">Syndic</a>';
    }
    return implode('', $chips);
}

function rowMetrics(string $rubrique, array $n1, array $entry = [], array $revenusParBien = []): array
{
    return [
        'value' => in_array($rubrique, ['immobilier', 'mobilierFinancier'], true) ? patrimonialValue($n1, $rubrique) : 0.0,
        'revenue' => rowRevenue($rubrique, $n1, $entry, $revenusParBien),
        'netRevenue' => $rubrique === 'immobilier' ? rowImmobilierNetRevenue($n1, $entry) : 0.0,
        'charge' => $rubrique === 'chargesAnnuelles' ? num($n1['montantAnnuel'] ?? 0) : ($rubrique === 'dettesCredits' ? num($n1['mensualiteAnnuelle'] ?? 0) : ($rubrique === 'immobilier' ? immobilierAnnualCharges($entry['niveau2'] ?? []) : 0.0)),
        'debt' => $rubrique === 'dettesCredits' ? num($n1['capitalRestantDu'] ?? 0) : 0.0,
    ];
}

function immobilierAnnualCharges(array $niveau2): float
{
    return num($niveau2['chargesNonRecuperablesAnnuelles'] ?? $niveau2['chargesNonRecuperables'] ?? 0)
        + num($niveau2['taxeFonciereAnnuelle'] ?? $niveau2['taxeFonciere'] ?? 0)
        + num($niveau2['assurancePNOAnnuelle'] ?? $niveau2['assurancePNO'] ?? 0)
        + num($niveau2['administrateurBienFraisAnnuel'] ?? $niveau2['administrateurBienFrais'] ?? 0)
        + num($niveau2['syndicBienFraisAnnuel'] ?? $niveau2['syndicBienFrais'] ?? 0);
}

function rowRevenue(string $rubrique, array $n1, array $entry, array $revenusParBien): float
{
    if ($rubrique === 'revenus') {
        return num($n1['montantAnnuel'] ?? 0);
    }
    if ($rubrique !== 'immobilier') {
        return 0.0;
    }
    $title = trim((string)($entry['titre'] ?? ''));
    $linkedRevenue = $title !== '' ? num($revenusParBien[$title] ?? 0) : 0.0;
    if ($linkedRevenue > 0) {
        return $linkedRevenue;
    }
    return num($entry['niveau2']['loyerAnnuel'] ?? $n1['loyerAnnuel'] ?? 0);
}

function rowImmobilierNetRevenue(array $n1, array $entry): float
{
    return immobilierNetAnnualRevenue($entry['niveau2'] ?? []) * usufruitFactor($n1);
}

function revenusByImmobilier(array $revenus): array
{
    $map = [];
    foreach ($revenus as $revenu) {
        $n1 = $revenu['niveau1'] ?? [];
        $bien = trim((string)($n1['bienImmobilierAssocie'] ?? ''));
        if ($bien === '' || $bien === 'Autre') {
            continue;
        }
        $map[$bien] = ($map[$bien] ?? 0) + num($n1['montantAnnuel'] ?? 0);
    }
    return $map;
}

function patrimonialValue(array $n1, string $rubrique): float
{
    return patrimoineEntryValue($n1, $rubrique);
}

function quotePartFactor(array $n1): float
{
    $quote = num($n1['quotePartDetenue'] ?? 1, 1);
    return $quote > 1 ? $quote / 100 : $quote;
}

function quotePartValue(array $n1): float
{
    return num($n1['valeurActuelle'] ?? 0) * quotePartFactor($n1);
}

function usufruitFactor(array $n1): float
{
    $usufruit = num($n1['pourcentageUsufruit'] ?? 100, 100);
    return $usufruit > 1 ? $usufruit / 100 : $usufruit;
}

function usufruitFiscalFactorUnder70(array $n1): float
{
    $quotePart = quotePartFactor($n1);
    if ($quotePart >= 1.0) {
        return 1.0;
    }
    return 0.4;
}

function usufruitFiscalValueUnder70(array $n1): float
{
    return num($n1['valeurActuelle'] ?? 0) * usufruitFiscalFactorUnder70($n1);
}

function formatQuotePartPercent(array $n1): string
{
    return formatPercentValue($n1['quotePartDetenue'] ?? 1, 1);
}

function formatPercentValue($value, float $default = 1.0): string
{
    $percent = percentFactor($value, $default) * 100;
    return number_format($percent, $percent === floor($percent) ? 0 : 1, ',', ' ') . ' %';
}

function formatEuroPercent(float $value, float $factor): string
{
    return euro($value) . ' (' . formatPercentFactor($factor) . ')';
}

function formatPercentFactor(float $factor): string
{
    $percent = $factor * 100;
    return number_format($percent, $percent === floor($percent) ? 0 : 1, ',', ' ') . ' %';
}
