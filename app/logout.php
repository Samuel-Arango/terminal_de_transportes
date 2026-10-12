<?php
require_once __DIR__ . '/includes/auth.php';

// Cerrar sesión solo mediante POST con token CSRF.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

require_csrf();
logoutUser();

header('Location: index.php');
exit;
