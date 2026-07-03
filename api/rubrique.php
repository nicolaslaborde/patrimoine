<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/calculations.php';
require_once __DIR__ . '/../includes/immobilier-revenus.php';
requireLogin();

$action = $_GET['action'] ?? 'list';
$rubrique = $_GET['rubrique'] ?? '';
$id = $_GET['id'] ?? null;

if (!in_array($rubrique, array_slice(RUBRIQUES, 1), true)) {
    jsonResponse(['error' => 'Rubrique invalide.'], 422);
}

$data = loadPatrimoine();

if ($action === 'schema') {
    jsonResponse(schemaForFiche($rubrique, (string)($_GET['type'] ?? '')));
}

if ($action === 'list') {
    jsonResponse(array_values($data[$rubrique] ?? []));
}

if ($action === 'get') {
    $entry = findFiche($data[$rubrique] ?? [], (string)$id);
    $entry ? jsonResponse($entry) : jsonResponse(['error' => 'Fiche introuvable.'], 404);
}

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $entry = sanitizeFiche($rubrique, readJsonBody());
    $entry['id'] = uuid();
    $entry['createdAt'] = nowIso();
    $entry['updatedAt'] = nowIso();
    $data[$rubrique][] = $entry;
    if ($rubrique === 'immobilier') {
        syncImmobilierRegimeFiscalRevenus($data);
    }
    savePatrimoine($data);
    jsonResponse($entry, 201);
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $index = findFicheIndex($data[$rubrique] ?? [], (string)$id);
    if ($index === null) {
        jsonResponse(['error' => 'Fiche introuvable.'], 404);
    }
    $entry = sanitizeFiche($rubrique, readJsonBody(), $data[$rubrique][$index]);
    $entry['id'] = $data[$rubrique][$index]['id'];
    $entry['createdAt'] = $data[$rubrique][$index]['createdAt'];
    $entry['updatedAt'] = nowIso();
    $data[$rubrique][$index] = $entry;
    if ($rubrique === 'immobilier') {
        syncImmobilierRegimeFiscalRevenus($data);
    }
    savePatrimoine($data);
    jsonResponse($entry);
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $index = findFicheIndex($data[$rubrique] ?? [], (string)$id);
    if ($index === null) {
        jsonResponse(['error' => 'Fiche introuvable.'], 404);
    }
    array_splice($data[$rubrique], $index, 1);
    if ($rubrique === 'immobilier') {
        syncImmobilierRegimeFiscalRevenus($data);
    }
    savePatrimoine($data);
    jsonResponse(['ok' => true]);
}

jsonResponse(['error' => 'Action invalide.'], 400);

function findFiche(array $entries, string $id): ?array
{
    $index = findFicheIndex($entries, $id);
    return $index === null ? null : $entries[$index];
}

function findFicheIndex(array $entries, string $id): ?int
{
    foreach ($entries as $index => $entry) {
        if (($entry['id'] ?? '') === $id) {
            return $index;
        }
    }
    return null;
}

function sanitizeFiche(string $rubrique, array $input, array $existing = []): array
{
    $baseSchema = schemaFor($rubrique);
    $type = (string)($input['type'] ?? '');
    if ($type !== '' && !in_array($type, $baseSchema['types'], true)) {
        jsonResponse(['error' => 'Type de fiche invalide.'], 422);
    }
    $schema = schemaForFiche($rubrique, $type);
    $schema['types'] = $baseSchema['types'];

    $entry = [
        'id' => $existing['id'] ?? '',
        'rubrique' => $rubrique,
        'type' => $type,
        'titre' => trim((string)($input['titre'] ?? '')),
        'createdAt' => $existing['createdAt'] ?? nowIso(),
        'updatedAt' => nowIso(),
        'niveau1' => sanitizeLevel($input['niveau1'] ?? [], $schema['niveau1'], $existing['niveau1'] ?? []),
        'niveau2' => sanitizeLevel($input['niveau2'] ?? [], $schema['niveau2'], $existing['niveau2'] ?? []),
        'liens' => sanitizeLinks($input['liens'] ?? []),
        'historique' => $rubrique === 'immobilier' ? sanitizeHistorique($input['historique'] ?? []) : ($existing['historique'] ?? []),
        'commentaire' => trim((string)($input['commentaire'] ?? '')),
    ];

    foreach (['niveau1', 'niveau2'] as $level) {
        foreach (($schema[$level] ?? []) as $field => $def) {
            if (!empty($def['periodic'])) {
                applyPeriodic($entry[$level], $field, $def['periodicBase'] ?: $field);
            }
        }
    }
    return $entry;
}

function sanitizeLevel(array $values, array $schema, array $existing = []): array
{
    $out = $existing;
    foreach ($schema as $field => $def) {
        if (!empty($def['periodic'])) {
            $raw = is_array($values[$field] ?? null) ? $values[$field] : [];
            $out[$field] = [
                'valeurSaisie' => num($raw['valeurSaisie'] ?? null),
                'periodicite' => in_array(($raw['periodicite'] ?? ''), ['mensuel', 'trimestriel', 'annuel'], true) ? $raw['periodicite'] : 'annuel',
                'mensuel' => num($raw['mensuel'] ?? null),
                'annuel' => num($raw['annuel'] ?? null),
            ];
            continue;
        }
        $value = $values[$field] ?? null;
        if ($def['type'] === 'number') {
            $out[$field] = $value === '' || $value === null ? null : (float)$value;
        } elseif ($def['type'] === 'date') {
            $out[$field] = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$value) ? $value : '';
        } elseif ($def['type'] === 'select') {
            $out[$field] = in_array((string)$value, $def['options'] ?? [], true) ? (string)$value : '';
        } elseif ($def['type'] === 'url') {
            $url = is_scalar($value) ? trim((string)$value) : '';
            if ($url !== '' && !validUrl($url)) {
                jsonResponse(['error' => "Lien invalide: {$url}"], 422);
            }
            $out[$field] = $url;
        } elseif (in_array($def['type'], ['textarea', 'text', 'password'], true)) {
            $out[$field] = is_scalar($value) ? trim((string)$value) : '';
        } else {
            $out[$field] = '';
        }
    }
    return $out;
}

function applyPeriodic(array &$level, string $field, string $base): void
{
    $periodic = $level[$field] ?? [];
    $value = num($periodic['valeurSaisie'] ?? 0);
    $period = $periodic['periodicite'] ?? 'annuel';
    if ($period === 'mensuel') {
        $monthly = $value;
        $annual = $value * 12;
    } elseif ($period === 'trimestriel') {
        $monthly = $value / 3;
        $annual = $value * 4;
    } else {
        $monthly = $value / 12;
        $annual = $value;
    }
    $level[$field] = ['valeurSaisie' => $value, 'periodicite' => $period, 'mensuel' => $monthly, 'annuel' => $annual];
    [$monthlyKey, $annualKey] = periodicDirectKeys($base);
    $level[$monthlyKey] = $monthly;
    $level[$annualKey] = $annual;
}

function periodicDirectKeys(string $base): array
{
    $map = [
        'mensualite' => ['mensualiteMensuelle', 'mensualiteAnnuelle'],
        'assuranceEmprunteur' => ['assuranceEmprunteurMensuelle', 'assuranceEmprunteurAnnuelle'],
        'chargesNonRecuperables' => ['chargesNonRecuperablesMensuelles', 'chargesNonRecuperablesAnnuelles'],
        'taxeFonciere' => ['taxeFonciereMensuelle', 'taxeFonciereAnnuelle'],
        'assurancePNO' => ['assurancePNOMensuelle', 'assurancePNOAnnuelle'],
    ];
    return $map[$base] ?? [$base . 'Mensuel', $base . 'Annuel'];
}

function sanitizeLinks(array $links): array
{
    $out = [];
    foreach ($links as $link) {
        $url = trim((string)($link['url'] ?? ''));
        if ($url === '') {
            continue;
        }
        if (!validUrl($url)) {
            jsonResponse(['error' => "Lien invalide: {$url}"], 422);
        }
        $out[] = [
            'url' => $url,
            'description' => trim((string)($link['description'] ?? '')),
            'createdAt' => $link['createdAt'] ?? nowIso(),
        ];
    }
    return $out;
}

function sanitizeHistorique(array $items): array
{
    $out = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $texte = trim((string)($item['texte'] ?? ''));
        $url = trim((string)($item['url'] ?? ''));
        if ($texte === '' && $url === '') {
            continue;
        }
        if ($url !== '' && !validUrl($url)) {
            jsonResponse(['error' => "Lien historique invalide: {$url}"], 422);
        }
        $date = (string)($item['date'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }
        $out[] = [
            'date' => $date,
            'texte' => $texte,
            'url' => $url,
        ];
    }
    return $out;
}
