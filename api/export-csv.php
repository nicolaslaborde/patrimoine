<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/calculations.php';
requireLogin();

$data = loadPatrimoine();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="patrimoine-synthese-' . preg_replace('/[^a-zA-Z0-9_-]+/', '-', currentPatrimoineOwner()) . '-' . date('Y-m-d') . '.csv"');
echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');
fputcsv($out, ['Rubrique', 'Type', 'Titre', 'Valeur patrimoniale', '% quote-part', 'Revenu annuel', 'Charge annuelle', 'Dette restante', 'Nombre de liens', 'Commentaire'], ';');
$schema = appSchema();
foreach (array_slice(RUBRIQUES, 1) as $rubrique) {
    foreach (($data[$rubrique] ?? []) as $entry) {
        $n1 = $entry['niveau1'] ?? [];
        $value = in_array($rubrique, ['immobilier', 'mobilierFinancier'], true) ? patrimoineEntryValue($n1, $rubrique) : 0;
        fputcsv($out, [
            $schema[$rubrique]['label'],
            $entry['type'] ?? '',
            $entry['titre'] ?? '',
            $value,
            $rubrique === 'immobilier' ? percentFactor($n1['quotePartDetenue'] ?? 1, 1) * 100 : '',
            $rubrique === 'revenus' ? num($n1['montantAnnuel'] ?? 0) : 0,
            $rubrique === 'chargesAnnuelles' ? num($n1['montantAnnuel'] ?? 0) : ($rubrique === 'dettesCredits' ? num($n1['mensualiteAnnuelle'] ?? 0) : 0),
            $rubrique === 'dettesCredits' ? num($n1['capitalRestantDu'] ?? 0) : 0,
            count($entry['liens'] ?? []),
            $entry['commentaire'] ?? '',
        ], ';');
    }
}
fclose($out);
