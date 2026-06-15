<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$data = loadPatrimoine();
header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="patrimoine-' . preg_replace('/[^a-zA-Z0-9_-]+/', '-', currentPatrimoineOwner()) . '-' . date('Y-m-d') . '.json"');
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
