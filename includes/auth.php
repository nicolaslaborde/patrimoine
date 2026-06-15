<?php
declare(strict_types=1);

require_once __DIR__ . '/storage.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function isLoggedIn(): bool
{
    return currentUser() !== null;
}

function isAdmin(): bool
{
    return (currentUser()['role'] ?? '') === 'admin';
}

function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        echo 'Accès réservé à l’administrateur.';
        exit;
    }
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
            jsonResponse(['error' => 'Authentification requise.'], 401);
        }
        redirect('login.php');
    }
}

function loginUser(string $username, string $password, bool $remember = false): bool
{
    foreach (loadUsers() as $user) {
        if (($user['username'] ?? '') === $username && password_verify($password, (string)$user['passwordHash'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'] ?? 'user',
                'patrimoineFile' => $user['patrimoineFile'] ?? patrimoineFilename((string)$user['username']),
            ];
            if ($remember) {
                setcookie('patrimoine_username', $username, time() + 60 * 60 * 24 * 365, '', '', false, true);
            }
            return true;
        }
    }
    return false;
}

function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
    }
    session_destroy();
}
