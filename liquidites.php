<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$data = loadPatrimoine();
$entries = [];
$total = 0.0;

foreach (($data['mobilierFinancier'] ?? []) as $entry) {
    $n1 = $entry['niveau1'] ?? [];
    if (($n1['liquidite'] ?? '') !== 'immédiate') {
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
  <table class="dense-table">
    <thead>
      <tr><th>Type</th><th>Titre</th><th>Établissement</th><th>Valeur</th><th>Quote-part</th><th>Liquidité</th><th>Commentaire</th><th></th></tr>
    </thead>
    <tbody>
      <?php if (!$entries): ?>
        <tr><td colspan="8">Aucune source de liquidité immédiate.</td></tr>
      <?php endif; ?>
      <?php foreach ($entries as $entry): ?>
        <tr>
          <td><?= e($entry['type']) ?></td>
          <td><strong><?= e($entry['titre']) ?></strong></td>
          <td><?= e($entry['etablissement']) ?></td>
          <td><?= e(euro($entry['valeur'])) ?></td>
          <td><?= e($entry['quotePart']) ?></td>
          <td><?= e($entry['liquidite']) ?></td>
          <td><?= e($entry['commentaire']) ?></td>
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
