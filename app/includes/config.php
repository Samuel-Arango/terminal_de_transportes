<?php
/**
 * Configuración de la aplicación.
 *
 * Las credenciales ya NO viven en el código. Se leen de variables de entorno
 * o de un archivo .env (nunca versionado). Si no existe .env se usan los
 * valores por defecto para un entorno local con XAMPP.
 */
declare(strict_types=1);

/** Carga un archivo .env sencillo (clave=valor) sin sobrescribir el entorno real. */
function load_env_file(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim(trim($value), "\"'");
        if ($key !== '' && getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

/** Devuelve el valor de una variable de entorno o el valor por defecto. */
function env_value(string $key, mixed $default = null): mixed
{
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

load_env_file(dirname(__DIR__) . '/.env');

define('APP_NAME',  (string) env_value('APP_NAME', 'Terminal de Transportes'));
define('APP_ENV',   (string) env_value('APP_ENV', 'local'));
define('APP_DEBUG', (bool)   env_value('APP_DEBUG', true));

define('DB_HOST', (string) env_value('DB_HOST', '127.0.0.1'));
define('DB_PORT', (string) env_value('DB_PORT', '3306'));
define('DB_NAME', (string) env_value('DB_NAME', 'terminal'));
define('DB_USER', (string) env_value('DB_USER', 'root'));
define('DB_PASS', (string) env_value('DB_PASS', ''));

/** Moneda usada en toda la interfaz. */
define('CURRENCY', '$');
