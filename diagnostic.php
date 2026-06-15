<?php
// Diagnostic minimal pour hébergement mutualisé. Supprimez ce fichier après vérification.
$checks = [
    'PHP version' => PHP_VERSION,
    'PHP >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>=') ? 'OK' : 'TROP ANCIEN',
    'json extension' => extension_loaded('json') ? 'OK' : 'MANQUANT',
    'sessions' => function_exists('session_start') ? 'OK' : 'MANQUANT',
    'password_hash' => function_exists('password_hash') ? 'OK' : 'MANQUANT',
    'data writable' => is_writable(__DIR__ . '/data') ? 'OK' : 'NON ECRITURE',
    'backups writable' => is_writable(__DIR__ . '/data/backups') ? 'OK' : 'NON ECRITURE',
    'users exists' => file_exists(__DIR__ . '/data/users.json') ? 'OK' : 'sera créé',
    'patrimoine exists' => file_exists(__DIR__ . '/data/patrimoine.json') ? 'OK' : 'sera créé',
    'patrimoine files' => implode(', ', array_map('basename', glob(__DIR__ . '/data/patrimoine*.json') ?: [])),
];
?><!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Diagnostic Patrimoine</title>
  <style>body{font-family:system-ui;margin:24px}td,th{padding:6px 10px;border:1px solid #ddd}table{border-collapse:collapse}</style>
</head>
<body>
  <h1>Diagnostic Patrimoine</h1>
  <table>
    <?php foreach ($checks as $label => $value): ?>
      <tr><th><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></th><td><?= htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') ?></td></tr>
    <?php endforeach; ?>
  </table>
</body>
</html>
