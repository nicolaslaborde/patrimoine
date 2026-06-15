<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/calculations.php';

$data = loadPatrimoine();
$dashboard = calculateDashboard($data);
$schema = appSchema();
$brut = (float)$dashboard['patrimoineBrutTotal'];
$net = (float)$dashboard['patrimoineNet'];
$detteRatio = $brut > 0 ? ((float)$dashboard['dettesTotales'] / $brut) * 100 : 0;
$postes = buildSynthesisPosts($data, $dashboard, $schema);

renderHeader('Synthèse de patrimoine');
?>
<div class="page-head dense-head">
  <div>
    <h1><?= e(pageH1('Synthèse de patrimoine')) ?></h1>
    <p class="muted">Vue consolidée par grands postes · <?= e(date('d/m/Y')) ?></p>
  </div>
  <div class="actions">
    <button class="button secondary" type="button" id="printSynthesis">Imprimer</button>
    <a class="button secondary" href="dashboard.php">Retour</a>
  </div>
</div>

<section class="synthesis-hero">
  <article><span>Patrimoine brut</span><strong><?= e(euro($brut)) ?></strong></article>
  <article><span>Dettes totales</span><strong><?= e(euro((float)$dashboard['dettesTotales'])) ?></strong></article>
  <article><span>Patrimoine net</span><strong><?= e(euro($net)) ?></strong></article>
  <article><span>Revenu net estimé/an</span><strong><?= e(euro((float)$dashboard['revenuNetAnnuelEstime'])) ?></strong></article>
</section>

<section class="synthesis-note">
  <p>
    Le patrimoine brut est principalement composé de
    <strong><?= e(euro((float)$dashboard['immobilierTotal'])) ?></strong> d’immobilier et
    <strong><?= e(euro((float)$dashboard['mobilierFinancierTotal'])) ?></strong> de mobilier/financier.
    Les dettes représentent <strong><?= e(number_format($detteRatio, 1, ',', ' ')) ?> %</strong> du patrimoine brut.
  </p>
</section>

<section class="table-section">
  <h2>Vue globale par grand poste</h2>
  <div class="table-wrap">
    <table class="dense-table">
      <thead>
        <tr>
          <th>Poste</th>
          <th>Fiches</th>
          <th>Valeur</th>
          <th>% brut</th>
          <th>Revenu/an</th>
          <th>Charge/an</th>
          <th>Dette</th>
          <th>Lecture rapide</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($postes as $index => $poste): ?>
          <tr>
            <td>
              <?php if ($poste['count'] > 0): ?>
                <button class="mini synth-toggle" type="button" data-target="poste-<?= e($index) ?>" aria-label="Afficher le détail">›</button>
              <?php endif; ?>
              <strong><?= e($poste['label']) ?></strong>
            </td>
            <td><?= e($poste['count']) ?></td>
            <td><?= e(euro($poste['value'])) ?></td>
            <td><?= e(number_format($poste['share'], 1, ',', ' ')) ?> %</td>
            <td><?= e(euro($poste['revenue'])) ?></td>
            <td><?= e(euro($poste['charge'])) ?></td>
            <td><?= e(euro($poste['debt'])) ?></td>
            <td><?= e($poste['note']) ?></td>
          </tr>
          <?php if ($poste['count'] > 0): ?>
            <tr class="poste-detail-row" id="poste-<?= e($index) ?>" hidden>
              <td colspan="8">
                <div class="poste-detail">
                  <table class="dense-table inner-table">
                    <thead>
                      <tr><th>Type</th><th>Titre</th><th><?= e($poste['rubrique'] === 'immobilier' ? 'Valeur (%)' : 'Valeur') ?></th><th>Revenu/an</th><th>Charge/an</th><th>Dette</th><th>Liens</th><th>Commentaire</th></tr>
                    </thead>
                    <tbody>
                      <?php foreach ($poste['entries'] as $entry): ?>
                        <tr>
                          <td><?= e($entry['type']) ?></td>
                          <td><?= e($entry['titre']) ?></td>
                          <td>
                            <?= e(euro($entry['value'])) ?>
                            <?php if ($poste['rubrique'] === 'immobilier'): ?>
                              <span class="muted">(<?= e($entry['quotePartPercent']) ?>)</span>
                            <?php endif; ?>
                          </td>
                          <td><?= e(euro($entry['revenue'])) ?></td>
                          <td><?= e(euro($entry['charge'])) ?></td>
                          <td><?= e(euro($entry['debt'])) ?></td>
                          <td>
                            <?php foreach ($entry['liens'] as $link): ?>
                              <a class="link-chip" href="<?= e($link['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($link['description'] ?: 'Lien') ?></a>
                            <?php endforeach; ?>
                          </td>
                          <td><?= e($entry['commentaire']) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </td>
            </tr>
          <?php endif; ?>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr class="total-row">
          <td>Total consolidé</td>
          <td></td>
          <td><?= e(euro($brut)) ?></td>
          <td>100 %</td>
          <td><?= e(euro((float)$dashboard['revenusAnnuelsTotaux'])) ?></td>
          <td><?= e(euro((float)$dashboard['chargesAnnuellesTotales'])) ?></td>
          <td><?= e(euro((float)$dashboard['dettesTotales'])) ?></td>
          <td>Patrimoine net : <?= e(euro($net)) ?></td>
        </tr>
      </tfoot>
    </table>
  </div>
</section>

<section class="synthesis-grid">
  <article class="compact-panel">
    <h2>Flux annuels</h2>
    <dl class="mini-stats">
      <dt>Revenus annuels</dt><dd><?= e(euro((float)$dashboard['revenusAnnuelsTotaux'])) ?></dd>
      <dt>Charges annuelles</dt><dd><?= e(euro((float)$dashboard['chargesAnnuellesTotales'])) ?></dd>
      <dt>Solde annuel estimé</dt><dd><?= e(euro((float)$dashboard['revenuNetAnnuelEstime'])) ?></dd>
    </dl>
  </article>
  <article class="compact-panel">
    <h2>Qualité des données</h2>
    <dl class="mini-stats">
      <dt>Informations manquantes</dt><dd><?= e($dashboard['nombreInformationsManquantes']) ?></dd>
      <dt>Dernière mise à jour</dt><dd><?= e($dashboard['derniereMiseAJour']) ?></dd>
      <dt>Liquidités</dt><dd><?= e(euro((float)$dashboard['liquiditesDisponibles'])) ?></dd>
    </dl>
  </article>
</section>
<?php renderFooter(); ?>

<?php
function buildSynthesisPosts(array $data, array $dashboard, array $schema): array
{
    $brut = (float)$dashboard['patrimoineBrutTotal'];
    $rows = [];
    foreach (array_slice(RUBRIQUES, 1) as $rubrique) {
        $entries = $data[$rubrique] ?? [];
        $value = in_array($rubrique, ['immobilier', 'mobilierFinancier'], true) ? sumPatrimoine($entries, $rubrique) : 0.0;
        $revenue = $rubrique === 'revenus' ? sumNiveau1($entries, 'montantAnnuel') : 0.0;
        if ($rubrique === 'chargesAnnuelles') {
            $charge = sumNiveau1($entries, 'montantAnnuel');
        } elseif ($rubrique === 'dettesCredits') {
            $charge = sumNiveau1($entries, 'mensualiteAnnuelle');
        } else {
            $charge = 0.0;
        }
        $debt = $rubrique === 'dettesCredits' ? sumNiveau1($entries, 'capitalRestantDu') : 0.0;
        $rows[] = [
            'rubrique' => $rubrique,
            'label' => $schema[$rubrique]['label'] ?? $rubrique,
            'count' => count($entries),
            'value' => $value,
            'share' => $brut > 0 ? ($value / $brut) * 100 : 0.0,
            'revenue' => $revenue,
            'charge' => $charge,
            'debt' => $debt,
        'note' => synthesisNote($rubrique, count($entries), $value, $revenue, $charge, $debt),
            'entries' => synthesisEntries($rubrique, $entries, $data),
        ];
    }
    return $rows;
}

function synthesisEntries(string $rubrique, array $entries, array $data): array
{
    $rows = [];
    $revenusParBien = $rubrique === 'immobilier' ? revenusByImmobilierForSynthesis($data['revenus'] ?? []) : [];
    foreach ($entries as $entry) {
        $n1 = $entry['niveau1'] ?? [];
        $title = trim((string)($entry['titre'] ?? ''));
        $immobilierRevenue = $rubrique === 'immobilier' ? num($revenusParBien[$title] ?? 0) : 0.0;
        if ($rubrique === 'immobilier' && $immobilierRevenue <= 0) {
            $immobilierRevenue = num($entry['niveau2']['loyerAnnuel'] ?? $n1['loyerAnnuel'] ?? 0);
        }
        $rows[] = [
            'type' => $entry['type'] ?? '',
            'titre' => $entry['titre'] ?? '',
            'value' => in_array($rubrique, ['immobilier', 'mobilierFinancier'], true) ? patrimoineEntryValue($n1, $rubrique) : 0.0,
            'quotePartPercent' => formatSynthesisQuotePartPercent($n1),
            'revenue' => $rubrique === 'revenus' ? num($n1['montantAnnuel'] ?? 0) : $immobilierRevenue,
            'charge' => $rubrique === 'chargesAnnuelles' ? num($n1['montantAnnuel'] ?? 0) : ($rubrique === 'dettesCredits' ? num($n1['mensualiteAnnuelle'] ?? 0) : 0.0),
            'debt' => $rubrique === 'dettesCredits' ? num($n1['capitalRestantDu'] ?? 0) : 0.0,
            'liens' => is_array($entry['liens'] ?? null) ? $entry['liens'] : [],
            'commentaire' => $entry['commentaire'] ?? '',
        ];
    }
    return $rows;
}

function formatSynthesisQuotePartPercent(array $n1): string
{
    $percent = percentFactor($n1['quotePartDetenue'] ?? 1, 1) * 100;
    return number_format($percent, $percent === floor($percent) ? 0 : 1, ',', ' ') . ' %';
}

function revenusByImmobilierForSynthesis(array $revenus): array
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

function synthesisNote(string $rubrique, int $count, float $value, float $revenue, float $charge, float $debt): string
{
    if ($count === 0) {
        return 'Aucune fiche renseignée.';
    }
    if ($rubrique === 'immobilier' || $rubrique === 'mobilierFinancier') {
        return 'Poste patrimonial valorisé.';
    }
    if ($rubrique === 'revenus') {
        return $revenue > 0 ? 'Alimente les revenus récurrents.' : 'Revenus à compléter.';
    }
    if ($rubrique === 'chargesAnnuelles') {
        return $charge > 0 ? 'Réduit le revenu net annuel.' : 'Charges à compléter.';
    }
    if ($rubrique === 'dettesCredits') {
        return $debt > 0 ? 'Impacte le patrimoine net et les charges.' : 'Dette sans capital restant renseigné.';
    }
    if ($rubrique === 'documents') {
        return 'Pièces justificatives et mémos associés.';
    }
    if ($rubrique === 'alertes') {
        return 'Échéances et rappels à suivre.';
    }
    return 'Informations de contexte patrimonial.';
}
