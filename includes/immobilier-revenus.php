<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function syncImmobilierRegimeFiscalRevenus(array &$data): void
{
    $revenusParRegime = immobilierNetRevenusByRegimeFiscal($data['immobilier'] ?? []);
    $seenRegimes = [];

    foreach (($data['revenus'] ?? []) as $index => $revenu) {
        $titre = trim((string)($revenu['titre'] ?? ''));
        if (!array_key_exists($titre, $revenusParRegime)) {
            continue;
        }
        $data['revenus'][$index] = buildRegimeFiscalRevenu($titre, $revenusParRegime[$titre], $revenu);
        $seenRegimes[$titre] = true;
    }

    foreach ($revenusParRegime as $regime => $montantAnnuel) {
        if (!isset($seenRegimes[$regime])) {
            $data['revenus'][] = buildRegimeFiscalRevenu($regime, $montantAnnuel);
        }
    }

    $data['revenus'] = array_values(array_filter($data['revenus'] ?? [], function (array $revenu) use ($revenusParRegime): bool {
        if (($revenu['sourceAutomatique'] ?? '') !== 'immobilierRegimeFiscal') {
            return true;
        }
        return array_key_exists(trim((string)($revenu['titre'] ?? '')), $revenusParRegime);
    }));
}

function immobilierNetRevenusByRegimeFiscal(array $immobilier): array
{
    $revenus = [];
    foreach (immobilierRegimeFiscalSources($immobilier) as $source) {
        $revenus[$source['regimeFiscal']] = ($revenus[$source['regimeFiscal']] ?? 0.0) + $source['revenuNetAnnuel'];
    }
    return $revenus;
}

function immobilierRegimeFiscalSources(array $immobilier, ?string $regimeFiscal = null): array
{
    $sources = [];
    foreach ($immobilier as $fiche) {
        $niveau1 = $fiche['niveau1'] ?? [];
        $niveau2 = $fiche['niveau2'] ?? [];
        $regime = trim((string)($niveau2['regimeFiscal'] ?? ''));
        if ($regime === '') {
            continue;
        }
        if ($regimeFiscal !== null && $regime !== $regimeFiscal) {
            continue;
        }
        $sources[] = [
            'id' => (string)($fiche['id'] ?? ''),
            'titre' => (string)($fiche['titre'] ?? ''),
            'regimeFiscal' => $regime,
            'revenuBrutAnnuel' => num($niveau2['loyerAnnuel'] ?? $niveau2['loyer'] ?? 0),
            'revenuNetAnnuel' => immobilierNetAnnualRevenue($niveau2) * immobilierUsufruitFactor($niveau1),
        ];
    }
    return $sources;
}

function immobilierNetAnnualRevenue(array $niveau2): float
{
    return num($niveau2['loyerAnnuel'] ?? $niveau2['loyer'] ?? 0)
        - num($niveau2['chargesNonRecuperablesAnnuelles'] ?? $niveau2['chargesNonRecuperables'] ?? 0)
        - num($niveau2['taxeFonciereAnnuelle'] ?? $niveau2['taxeFonciere'] ?? 0)
        - num($niveau2['assurancePNOAnnuelle'] ?? $niveau2['assurancePNO'] ?? 0);
}

function immobilierUsufruitFactor(array $niveau1): float
{
    $usufruit = num($niveau1['pourcentageUsufruit'] ?? 100, 100);
    return $usufruit > 1 ? $usufruit / 100 : $usufruit;
}

function buildRegimeFiscalRevenu(string $regime, float $montantAnnuel, array $existing = []): array
{
    $now = nowIso();
    return [
        'id' => $existing['id'] ?? uuid(),
        'rubrique' => 'revenus',
        'type' => 'Autre revenu',
        'titre' => $regime,
        'createdAt' => $existing['createdAt'] ?? $now,
        'updatedAt' => $now,
        'sourceAutomatique' => 'immobilierRegimeFiscal',
        'niveau1' => [
            'organismePayeur' => 'Immobilier',
            'bienImmobilierAssocie' => '',
            'montant' => [
                'valeurSaisie' => $montantAnnuel,
                'periodicite' => 'annuel',
                'mensuel' => $montantAnnuel / 12,
                'annuel' => $montantAnnuel,
            ],
            'fiscalise' => 'oui',
            'montantMensuel' => $montantAnnuel / 12,
            'montantAnnuel' => $montantAnnuel,
        ],
        'niveau2' => [
            'dateDebut' => $existing['niveau2']['dateDebut'] ?? '',
            'dateFin' => $existing['niveau2']['dateFin'] ?? '',
            'reversionPossible' => $existing['niveau2']['reversionPossible'] ?? '',
            'conditions' => 'Calcul automatique depuis les fiches Immobilier: loyer - charges non récupérables - taxe foncière - assurance PNO.',
        ],
        'liens' => $existing['liens'] ?? [],
        'commentaire' => 'Revenu net immobilier calculé automatiquement par régime fiscal.',
    ];
}
