<?php
/**
 * Autenticación, sesiones seguras, CSRF y utilidades de acceso.
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/** Inicia la sesión una sola vez con parámetros de cookie seguros. */
function boot_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',          // sin dominio fijo: evita Host Header Injection
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_name('TERMINALSESS');
    session_start();
}

boot_session();

/* ------------------------------------------------------------------ *
 * CSRF
 * ------------------------------------------------------------------ */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf(?string $token): bool
{
    return is_string($token)
        && !empty($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $token);
}

/** Aborta la petición si un POST no trae un token CSRF válido. */
function require_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf($_POST['csrf'] ?? null)) {
        http_response_code(419);
        exit('Sesión expirada o token de seguridad inválido. Recarga la página e inténtalo de nuevo.');
    }
}

/* ------------------------------------------------------------------ *
 * Sesión de usuario
 * ------------------------------------------------------------------ */
function isLogged(): bool
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']);
}

function user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_role(string ...$roles): bool
{
    return isLogged() && in_array(user()['rol'] ?? '', $roles, true);
}

function login(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user']          = $user;
    $_SESSION['last_activity'] = time();
}

function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Rutas relativas al directorio /app. */
function requireRole(array $roles = []): void
{
    if (!isLogged() || !in_array(user()['rol'] ?? '', $roles, true)) {
        header('Location: login.php?error=permiso');
        exit;
    }
}

function require_login(): void
{
    if (!isLogged()) {
        header('Location: login.php?error=login');
        exit;
    }
}

/** Cierra la sesión tras un periodo de inactividad (30 min). */
function enforce_idle_timeout(int $seconds = 1800): void
{
    if (!isLogged()) {
        return;
    }
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $seconds) {
        logoutUser();
        header('Location: login.php?error=timeout');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

/* ------------------------------------------------------------------ *
 * Mensajes flash
 * ------------------------------------------------------------------ */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/* ------------------------------------------------------------------ *
 * Utilidades
 * ------------------------------------------------------------------ */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money(float|int|string $amount): string
{
    return CURRENCY . number_format((float) $amount, 0, ',', '.');
}
