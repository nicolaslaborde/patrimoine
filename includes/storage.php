<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/schema.php';

function ensureStorage(): void
{
    foreach ([DATA_DIR, BACKUP_DIR] as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    if (!file_exists(DATA_DIR . '/.htaccess')) {
        file_put_contents(DATA_DIR . '/.htaccess', "Require all denied\n");
    }
    if (!file_exists(USERS_FILE)) {
        writeJsonAtomic(USERS_FILE, [[
            'id' => 'user-1',
            'username' => INITIAL_USERNAME,
            'passwordHash' => password_hash(INITIAL_PASSWORD, PASSWORD_DEFAULT),
            'role' => 'admin',
            'patrimoineFile' => 'patrimoine-nicolas.json',
            'createdAt' => nowIso(),
        ]], false);
    } else {
        $users = readJson(USERS_FILE);
        $changed = false;
        foreach ($users as &$user) {
            if (empty($user['patrimoineFile'])) {
                $user['patrimoineFile'] = ($user['username'] ?? '') === INITIAL_USERNAME ? 'patrimoine-nicolas.json' : patrimoineFilename((string)($user['username'] ?? $user['id']));
                $changed = true;
            }
        }
        unset($user);
        if ($changed) {
            writeJsonAtomic(USERS_FILE, $users, false);
        }
    }
    if (!file_exists(PATRIMOINE_FILE)) {
        writeJsonAtomic(PATRIMOINE_FILE, initialPatrimoine(), false);
    } else {
        $data = readJson(PATRIMOINE_FILE);
        if (($data['metadata']['version'] ?? '') !== '2.0') {
            backupPatrimoine();
            writeJsonAtomic(PATRIMOINE_FILE, migrateToV2($data), false);
        }
    }
}

function readJson(string $path): array
{
    $content = file_get_contents($path) ?: '';
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
    if ((substr($content, 0, 2) === "\xFF\xFE" || substr($content, 0, 2) === "\xFE\xFF") && function_exists('mb_convert_encoding')) {
        $converted = mb_convert_encoding($content, 'UTF-8', 'UTF-16');
        $content = $converted === false ? $content : $converted;
    }
    $data = json_decode($content, true);
    if (!is_array($data)) {
        throw new RuntimeException("JSON invalide: {$path}");
    }
    return $data;
}

function writeJsonAtomic(string $path, array $data, bool $backup = true): void
{
    if ($backup && substr(basename($path), 0, 10) === 'patrimoine' && file_exists($path)) {
        backupPatrimoine($path);
    }
    $tmp = dirname($path) . '/' . basename($path, '.json') . '.tmp.json';
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new RuntimeException('Impossible d’encoder les données JSON.');
    }
    file_put_contents($tmp, $json . PHP_EOL, LOCK_EX);
    rename($tmp, $path);
}

function backupPatrimoine(?string $path = null): void
{
    $path ??= currentPatrimoineFile();
    if (!file_exists($path)) {
        return;
    }
    if (!is_dir(BACKUP_DIR)) {
        mkdir(BACKUP_DIR, 0755, true);
    }
    $base = basename($path, '.json');
    copy($path, BACKUP_DIR . '/' . $base . '-' . date('Y-m-d-H-i-s') . '.json');
    $files = glob(BACKUP_DIR . '/patrimoine-*.json') ?: [];
    usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
    foreach (array_slice($files, 50) as $file) {
        @unlink($file);
    }
}

function loadUsers(): array
{
    ensureStorage();
    return readJson(USERS_FILE);
}

function saveUsers(array $users): array
{
    writeJsonAtomic(USERS_FILE, array_values($users), false);
    return array_values($users);
}

function findUserByUsername(string $username): ?array
{
    foreach (loadUsers() as $user) {
        if (strcasecmp((string)($user['username'] ?? ''), $username) === 0) {
            return $user;
        }
    }
    return null;
}

function createUser(string $username, string $password, string $role = 'user'): array
{
    $username = trim($username);
    if (!preg_match('/^[a-zA-Z0-9_-]{3,40}$/', $username)) {
        throw new InvalidArgumentException('Identifiant invalide. Utilisez 3 à 40 caractères: lettres, chiffres, tiret ou underscore.');
    }
    if (strlen($password) < 6) {
        throw new InvalidArgumentException('Le mot de passe doit contenir au moins 6 caractères.');
    }
    if (findUserByUsername($username)) {
        throw new InvalidArgumentException('Cet utilisateur existe déjà.');
    }

    $file = patrimoineFilename($username);
    $users = loadUsers();
    $user = [
        'id' => 'user-' . uuid(),
        'username' => $username,
        'passwordHash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role === 'admin' ? 'admin' : 'user',
        'patrimoineFile' => $file,
        'createdAt' => nowIso(),
    ];
    $users[] = $user;
    saveUsers($users);
    if (!file_exists(DATA_DIR . '/' . $file)) {
        writeJsonAtomic(DATA_DIR . '/' . $file, initialPatrimoine($username), false);
    }
    return $user;
}

function loadPatrimoine(): array
{
    ensureStorage();
    $path = currentPatrimoineFile();
    if (!file_exists($path)) {
        writeJsonAtomic($path, initialPatrimoine(currentPatrimoineOwner()), false);
    }
    return normalizePatrimoine(readJson($path));
}

function savePatrimoine(array $data): array
{
    $data = normalizePatrimoine($data);
    $data['metadata']['updatedAt'] = nowIso();
    writeJsonAtomic(currentPatrimoineFile(), $data, true);
    return $data;
}

function currentPatrimoineFile(): string
{
    $file = $_SESSION['user']['patrimoineFile'] ?? 'patrimoine-nicolas.json';
    $file = basename((string)$file);
    if (!preg_match('/^patrimoine[-a-zA-Z0-9_]*\.json$/', $file)) {
        $file = 'patrimoine-nicolas.json';
    }
    return DATA_DIR . '/' . $file;
}

function currentPatrimoineOwner(): string
{
    return (string)($_SESSION['user']['username'] ?? APP_OWNER);
}

function patrimoineFilename(string $username): string
{
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9_-]+/', '-', trim($username)) ?? '');
    $slug = trim($slug, '-_');
    return 'patrimoine-' . ($slug ?: uuid()) . '.json';
}

function initialPatrimoine(?string $owner = null): array
{
    $now = nowIso();
    $owner ??= APP_OWNER;
    $data = [
        'metadata' => ['version' => '2.0', 'createdAt' => $now, 'updatedAt' => $now, 'owner' => $owner],
        'profil' => [
            'nomDossier' => 'Patrimoine ' . ucfirst($owner),
            'typeFoyer' => '',
            'situationFamiliale' => '',
            'regimeMatrimonial' => '',
            'nombreEnfants' => null,
            'personnesACharge' => '',
            'objectifs' => 'Suivi et consolidation du patrimoine personnel',
            'commentaires' => '',
            'liens' => [],
        ],
    ];
    foreach (array_slice(RUBRIQUES, 1) as $rubrique) {
        $data[$rubrique] = [];
    }
    return $data;
}

function normalizePatrimoine(array $data): array
{
    $base = initialPatrimoine();
    $data['metadata'] = array_replace($base['metadata'], $data['metadata'] ?? []);
    $data['metadata']['version'] = '2.0';
    $data['profil'] = array_replace($base['profil'], $data['profil'] ?? []);
    $data['profil']['liens'] = is_array($data['profil']['liens'] ?? null) ? $data['profil']['liens'] : [];
    foreach (array_slice(RUBRIQUES, 1) as $rubrique) {
        $data[$rubrique] = array_values(is_array($data[$rubrique] ?? null) ? $data[$rubrique] : []);
    }
    return $data;
}

function migrateToV2(array $old): array
{
    $new = initialPatrimoine();
    $new['profil'] = array_replace($new['profil'], $old['profil'] ?? []);
    $new['metadata']['createdAt'] = $old['metadata']['createdAt'] ?? $new['metadata']['createdAt'];

    $map = [
        'revenusProfessionnels' => 'revenus',
        'retraitesRentes' => 'revenus',
        'dettes' => 'dettesCredits',
        'chargesAnnuelles' => 'chargesAnnuelles',
        'residencePrincipale' => 'immobilier',
        'immobilierLocatif' => 'immobilier',
        'comptesBancaires' => 'mobilierFinancier',
        'placementsFinanciers' => 'mobilierFinancier',
        'assurancesVie' => 'mobilierFinancier',
        'biensMobiliers' => 'mobilierFinancier',
        'societesParts' => 'mobilierFinancier',
        'fiscalite' => 'fiscalite',
        'successionTransmission' => 'successionTransmission',
        'documents' => 'documents',
        'alertes' => 'alertes',
    ];
    foreach ($map as $oldKey => $newKey) {
        foreach (($old[$oldKey] ?? []) as $entry) {
            if (is_array($entry)) {
                $new[$newKey][] = legacyEntryToFiche($newKey, $entry);
            }
        }
    }
    return normalizePatrimoine($new);
}

function legacyEntryToFiche(string $rubrique, array $entry): array
{
    $title = $entry['titre'] ?? $entry['nom'] ?? $entry['adresse'] ?? $entry['banque'] ?? $entry['nomContrat'] ?? $entry['nomSociete'] ?? $entry['nomCharge'] ?? 'Fiche importée';
    return [
        'id' => $entry['id'] ?? uuid(),
        'rubrique' => $rubrique,
        'type' => $entry['type'] ?? $entry['typeRevenu'] ?? $entry['typeDette'] ?? $entry['typeCompte'] ?? $entry['typePlacement'] ?? $entry['categorie'] ?? 'Autre',
        'titre' => $title,
        'createdAt' => $entry['createdAt'] ?? nowIso(),
        'updatedAt' => $entry['updatedAt'] ?? nowIso(),
        'niveau1' => $entry,
        'niveau2' => [],
        'liens' => [],
        'commentaire' => $entry['commentaire'] ?? '',
    ];
}
