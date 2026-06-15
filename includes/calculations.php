<?php
declare(strict_types=1);

require_once __DIR__ . '/schema.php';
require_once __DIR__ . '/helpers.php';

function calculateDashboard(array $data): array
{
    $immobilierTotal = sumPatrimoine($data['immobilier'] ?? [], 'immobilier');
    $mobilierFinancierTotal = sumPatrimoine($data['mobilierFinancier'] ?? [], 'mobilierFinancier');
    $dettesTotales = sumNiveau1($data['dettesCredits'] ?? [], 'capitalRestantDu');
    $revenusAnnuelsTotaux = sumNiveau1($data['revenus'] ?? [], 'montantAnnuel');
    $chargesAnnuellesTotales = sumNiveau1($data['chargesAnnuelles'] ?? [], 'montantAnnuel') + sumNiveau1($data['dettesCredits'] ?? [], 'mensualiteAnnuelle');
    $missing = getMissingInformation($data);

    return [
        'patrimoineBrutTotal' => $immobilierTotal + $mobilierFinancierTotal,
        'dettesTotales' => $dettesTotales,
        'patrimoineNet' => $immobilierTotal + $mobilierFinancierTotal - $dettesTotales,
        'immobilierTotal' => $immobilierTotal,
        'mobilierFinancierTotal' => $mobilierFinancierTotal,
        'liquiditesDisponibles' => liquiditesDisponibles($data['mobilierFinancier'] ?? []),
        'revenusAnnuelsTotaux' => $revenusAnnuelsTotaux,
        'chargesAnnuellesTotales' => $chargesAnnuellesTotales,
        'revenuNetAnnuelEstime' => $revenusAnnuelsTotaux - $chargesAnnuellesTotales,
        'nombreFichesImmobilier' => count($data['immobilier'] ?? []),
        'nombreFichesMobilierFinancier' => count($data['mobilierFinancier'] ?? []),
        'nombreRevenus' => count($data['revenus'] ?? []),
        'nombreDettes' => count($data['dettesCredits'] ?? []),
        'nombreCharges' => count($data['chargesAnnuelles'] ?? []),
        'nombreInformationsManquantes' => count($missing),
        'derniereMiseAJour' => $data['metadata']['updatedAt'] ?? '',
        'rubriques' => rubriqueSummaries($data, $missing),
    ];
}

function sumPatrimoine(array $entries, string $rubrique = ''): float
{
    return array_reduce($entries, function (float $sum, array $entry) use ($rubrique): float {
        $n1 = $entry['niveau1'] ?? [];
        $entryRubrique = $rubrique !== '' ? $rubrique : (string)($entry['rubrique'] ?? '');
        return $sum + patrimoineEntryValue($n1, $entryRubrique);
    }, 0.0);
}

function patrimoineEntryValue(array $n1, string $rubrique): float
{
    return num($n1['valeurActuelle'] ?? 0) * percentFactor($n1['quotePartDetenue'] ?? 1, 1);
}

function percentFactor($value, float $default = 1.0): float
{
    $factor = num($value, $default);
    return $factor > 1 ? $factor / 100 : $factor;
}

function sumNiveau1(array $entries, string $field): float
{
    return array_reduce($entries, fn(float $sum, array $entry): float => $sum + num(($entry['niveau1'] ?? [])[$field] ?? 0), 0.0);
}

function liquiditesDisponibles(array $entries): float
{
    return array_reduce($entries, function (float $sum, array $entry): float {
        $n1 = $entry['niveau1'] ?? [];
        return (($n1['liquidite'] ?? '') === 'immédiate') ? $sum + num($n1['valeurActuelle'] ?? 0) : $sum;
    }, 0.0);
}

function rubriqueSummaries(array $data, array $missing): array
{
    $schema = appSchema();
    $rows = [];
    foreach (RUBRIQUES as $rubrique) {
        $entries = $rubrique === 'profil' ? [] : ($data[$rubrique] ?? []);
        $rows[] = [
            'key' => $rubrique,
            'label' => $schema[$rubrique]['label'],
            'count' => $rubrique === 'profil' ? 1 : count($entries),
            'value' => in_array($rubrique, ['immobilier', 'mobilierFinancier'], true) ? sumPatrimoine($entries, $rubrique) : 0,
            'revenus' => $rubrique === 'revenus' ? sumNiveau1($entries, 'montantAnnuel') : 0,
            'charges' => rubriqueCharges($rubrique, $entries),
            'missing' => count(array_filter($missing, fn($m) => $m['rubrique'] === $rubrique)),
        ];
    }
    return $rows;
}

function getMissingInformation(array $data): array
{
    $schema = appSchema();
    $missing = [];
    foreach ($schema['profil']['niveau1Required'] as $field) {
        if (emptyValue($data['profil'][$field] ?? null)) {
            $missing[] = missingItem('profil', null, $data['profil']['nomDossier'] ?? 'Profil', $field);
        }
    }
    foreach (array_slice(RUBRIQUES, 1) as $rubrique) {
        foreach (($data[$rubrique] ?? []) as $entry) {
            $entrySchema = schemaForFiche($rubrique, (string)($entry['type'] ?? ''));
            foreach (($entrySchema['niveau1Required'] ?? []) as $field) {
                $value = in_array($field, ['type', 'titre'], true) ? ($entry[$field] ?? null) : (($entry['niveau1'] ?? [])[$field] ?? null);
                if (emptyValue($value)) {
                    $missing[] = missingItem($rubrique, $entry['id'] ?? null, $entry['titre'] ?? 'Fiche sans titre', $field);
                }
            }
        }
    }
    return $missing;
}

function rubriqueCharges(string $rubrique, array $entries): float
{
    if ($rubrique === 'chargesAnnuelles') {
        return sumNiveau1($entries, 'montantAnnuel');
    }
    if ($rubrique === 'dettesCredits') {
        return sumNiveau1($entries, 'mensualiteAnnuelle');
    }
    return 0.0;
}

function emptyValue($value): bool
{
    return $value === null || $value === '';
}

function missingItem(string $rubrique, ?string $entryId, string $entryLabel, string $field): array
{
    $strong = ['valeurActuelle', 'quotePartDetenue', 'capitalRestantDu', 'montantAnnuel', 'mensualiteAnnuelle'];
    return [
        'rubrique' => $rubrique,
        'entryId' => $entryId,
        'entryLabel' => $entryLabel,
        'field' => $field,
        'niveau' => 'niveau1',
        'importance' => in_array($field, $strong, true) ? 'forte' : 'moyenne',
        'message' => "Le champ {$field} est nécessaire pour consolider le bilan patrimonial.",
    ];
}
