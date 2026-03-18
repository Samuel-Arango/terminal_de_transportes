<?php
session_start();
if (session_status() === PHP_SESSION_NONE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

function isLogged() {
  return isset($_SESSION['user']) && is_array($_SESSION['user']);
}

function user() {
  return $_SESSION['user'] ?? null;
}

function requireRole($roles = []) {
  if (!isLogged() || !in_array(user()['rol'], $roles)) {
    header('Location: login.php?error=permiso');
    exit;
  }
}


function login(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user'] = $user;

}


function logoutUser(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
