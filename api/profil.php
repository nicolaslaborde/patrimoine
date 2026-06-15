<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$data = loadPatrimoine();
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    jsonResponse($data['profil']);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schema = schemaFor('profil');
    $input = readJsonBody();
    foreach (['niveau1', 'niveau2'] as $level) {
        foreach (($schema[$level] ?? []) as $field => $def) {
            $value = $input[$level][$field] ?? null;
            if ($def['type'] === 'number') {
                $data['profil'][$field] = $value === '' || $value === null ? null : (float)$value;
            } elseif ($def['type'] === 'select') {
                $data['profil'][$field] = in_array((string)$value, $def['options'] ?? [], true) ? (string)$value : '';
            } else {
                $data['profil'][$field] = is_scalar($value) ? trim((string)$value) : '';
            }
        }
    }
    $data['profil']['liens'] = [];
    foreach (($input['liens'] ?? []) as $link) {
        $url = trim((string)($link['url'] ?? ''));
        if ($url === '') {
            continue;
        }
        if (!validUrl($url)) {
            jsonResponse(['error' => "Lien invalide: {$url}"], 422);
        }
        $data['profil']['liens'][] = [
            'url' => $url,
            'description' => trim((string)($link['description'] ?? '')),
            'createdAt' => $link['createdAt'] ?? nowIso(),
        ];
    }
    $data['profil']['commentaires'] = trim((string)($input['commentaire'] ?? ''));
    savePatrimoine($data);
    jsonResponse($data['profil']);
}
jsonResponse(['error' => 'Méthode invalide.'], 405);
