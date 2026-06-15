<?php
declare(strict_types=1);

function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function jsonResponse($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function readJsonBody(): array
{
    $data = json_decode(file_get_contents('php://input') ?: '{}', true);
    return is_array($data) ? $data : [];
}

function nowIso(): string
{
    return (new DateTimeImmutable('now'))->format(DateTimeInterface::ATOM);
}

function uuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function num($value, float $default = 0.0): float
{
    if (is_array($value)) {
        return num($value['annuel'] ?? $value['mensuel'] ?? $value['valeurSaisie'] ?? null, $default);
    }
    return is_numeric($value) ? (float)$value : $default;
}

function redirect(string $url): void
{
    header("Location: {$url}");
    exit;
}

function currentPath(): string
{
    return basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');
}

function validUrl(string $url): bool
{
    return (bool)preg_match('#^https?://#i', $url) && filter_var($url, FILTER_VALIDATE_URL);
}
