<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
ensureStorage();
redirect(isLoggedIn() ? 'dashboard.php' : 'login.php');
