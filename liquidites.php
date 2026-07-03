<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$data = loadPatrimoine();
$entries = [];
$total = 0.0;

foreach (($data['mobilierFinancier'] ?? []) as $entry) {
    $n1 = $entry['niveau1'] ?? [];
    if (!in_array(($n1['liquidite'] ?? ''), ['immédiate', 'immÃ©diate'], true)) {
        continue;
    }
    $value = num($n1['valeurActuelle'] ?? 0);
    $total += $value;
    $entries[] = [
        'id' => $entry['id'] ?? '',
        'type' => $entry['type'] ?? '',
        'titre' => $entry['titre'] ?? '',
        'etablissement' => $n1['etablissement'] ?? '',
        'valeur' => $value,
        'quotePart' => num($n1['quotePartDetenue'] ?? 1, 1),
        'liquidite' => $n1['liquidite'] ?? '',
        'commentaire' => $entry['commentaire'] ?? '',
    ];
}

renderHeader('Sources des liquidités');
?>
<div class="page-head dense-head">
  <div>
    <h1><?= e(pageH1('Sources des liquidités')) ?></h1>
    <p class="muted">Fiches Mobilier et financier avec liquidité immédiate</p>
  </div>
  <div class="actions">
    <a class="button secondary" href="dashboard.php">Retour tableau de bord</a>
    <a class="button primary" href="rubrique.php?rubrique=mobilierFinancier">Voir mobilier et financier</a>
  </div>
</div>

<section class="metrics compact-grid">
  <article class="metric"><span>Total liquidités</span><strong><?= e(euro($total)) ?></strong></article>
  <article class="metric"><span>Nombre de sources</span><strong><?= e(count($entries)) ?></strong></article>
</section>

<div class="table-wrap">
  <table id="liquidites-table" class="dense-table">
    <thead>
      <tr>
        <th><?= sortHeader('liquidites-table', 0, 'text', 'Type') ?></th>
        <th><?= sortHeader('liquidites-table', 1, 'text', 'Titre') ?></th>
        <th><?= sortHeader('liquidites-table', 2, 'text', 'Établissement') ?></th>
        <th><?= sortHeader('liquidites-table', 3, 'number', 'Valeur') ?></th>
        <th><?= sortHeader('liquidites-table', 4, 'number', 'Quote-part') ?></th>
        <th><?= sortHeader('liquidites-table', 5, 'text', 'Liquidité') ?></th>
        <th><?= sortHeader('liquidites-table', 6, 'text', 'Commentaire') ?></th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$entries): ?>
        <tr><td colspan="8">Aucune source de liquidité immédiate.</td></tr>
      <?php endif; ?>
      <?php foreach ($entries as $entry): ?>
        <tr>
          <td data-sort-value="<?= e($entry['type']) ?>"><?= e($entry['type']) ?></td>
          <td data-sort-value="<?= e($entry['titre']) ?>"><strong><?= e($entry['titre']) ?></strong></td>
          <td data-sort-value="<?= e($entry['etablissement']) ?>"><?= e($entry['etablissement']) ?></td>
          <td data-sort-value="<?= e($entry['valeur']) ?>"><?= e(euro($entry['valeur'])) ?></td>
          <td data-sort-value="<?= e($entry['quotePart']) ?>"><?= e($entry['quotePart']) ?></td>
          <td data-sort-value="<?= e($entry['liquidite']) ?>"><?= e($entry['liquidite']) ?></td>
          <td data-sort-value="<?= e($entry['commentaire']) ?>"><?= e($entry['commentaire']) ?></td>
          <td><a class="button tiny secondary" href="form.php?rubrique=mobilierFinancier&id=<?= e($entry['id']) ?>">Modifier</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr class="total-row"><td colspan="3">Total</td><td><?= e(euro($total)) ?></td><td colspan="4"></td></tr>
    </tfoot>
  </table>
</div>
<?php renderFooter(); ?>

<?php
function sortHeader(string $tableId, int $column, string $type, string $label): string
{
    return '<button class="table-sort" type="button" data-sort-table="' . e($tableId) . '" data-sort-column="' . e($column) . '" data-sort-type="' . e($type) . '">' . e($label) . '</button>';
}
