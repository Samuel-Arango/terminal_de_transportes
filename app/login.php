<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$error     = '';
$errorCode = $_GET['error'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $email = trim($_POST['email'] ?? '');
    $pass  = (string) ($_POST['pass'] ?? '');

    // Limitación simple de intentos por sesión.
    $_SESSION['login_fails'] = $_SESSION['login_fails'] ?? 0;
    $_SESSION['login_block'] = $_SESSION['login_block'] ?? 0;

    if ($_SESSION['login_block'] > time()) {
        $error = 'Demasiados intentos fallidos. Espera unos segundos e inténtalo de nuevo.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $pass === '') {
        $error = 'Ingresa un correo electrónico y una contraseña válidos.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT u.id, u.nombre, u.email, u.pass_hash, u.activo, r.nombre AS rol
             FROM usuarios u
             JOIN roles r ON r.id = u.rol_id
             WHERE u.email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if ($u && (int) $u['activo'] === 1 && password_verify($pass, $u['pass_hash'])) {
            // Re-hash automático si el algoritmo cambió.
            if (password_needs_rehash($u['pass_hash'], PASSWORD_DEFAULT)) {
                $pdo->prepare('UPDATE usuarios SET pass_hash = ? WHERE id = ?')
                    ->execute([password_hash($pass, PASSWORD_DEFAULT), $u['id']]);
            }

            $_SESSION['login_fails'] = 0;
            login([
                'id'     => (int) $u['id'],
                'nombre' => $u['nombre'],
                'email'  => $u['email'],
                'rol'    => $u['rol'],
            ]);
            header('Location: dashboard.php');
            exit;
        }

        $_SESSION['login_fails']++;
        if ($_SESSION['login_fails'] >= 5) {
            $_SESSION['login_block'] = time() + 30;
            $_SESSION['login_fails'] = 0;
            $error = 'Demasiados intentos fallidos. Intenta de nuevo en 30 segundos.';
        } else {
            $error = 'Correo o contraseña incorrectos.';
        }
    }
}

if ($errorCode === 'timeout') {
    flash('info', 'Tu sesión expiró por inactividad. Inicia sesión nuevamente.');
} elseif ($errorCode === 'permiso') {
    flash('error', 'No tienes permisos para acceder a esa sección.');
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap">
  <div class="auth-card">
    <div class="auth-brand">
      <span class="auth-brand-badge" aria-hidden="true">TT</span>
      <h1>Iniciar sesión</h1>
      <p class="muted">Accede a tu cuenta de <?= e(APP_NAME) ?></p>
    </div>

    <?php if ($error !== ''): ?>
      <div class="msg error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="form" autocomplete="on">
      <?= csrf_field() ?>
      <label>Correo electrónico
        <input type="email" name="email" placeholder="tu@correo.com"
               value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
      </label>
      <label>Contraseña
        <input type="password" name="pass" placeholder="••••••••" required>
      </label>
      <button type="submit" class="btn btn-primary btn-block">Ingresar</button>
    </form>

    <div class="demo-box">
      <strong>Accesos de prueba</strong> (clave: <code>123456</code>)
      <ul>
        <li><code>admin@terminal.com</code> — Administrador</li>
        <li><code>empresa@terminal.com</code> — Empresa</li>
        <li><code>cliente@terminal.com</code> — Cliente</li>
      </ul>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
