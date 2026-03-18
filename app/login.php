<?php
require_once __DIR__.'/includes/db.php';
require_once __DIR__.'/includes/auth.php';

$error = $_GET['error'] ?? '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $email = $_POST['email'] ?? '';
  $pass  = $_POST['pass'] ?? '';
  $stmt = $pdo->prepare("
    SELECT u.id, u.nombre, u.email, r.nombre AS rol, u.pass_hash
    FROM usuarios u JOIN roles r ON r.id=u.rol_id
    WHERE u.email=? LIMIT 1");
  $stmt->execute([$email]);
  $u = $stmt->fetch();
  if ($u && hash('sha256',$pass)===$u['pass_hash']) {
    $_SESSION['user'] = ['id'=>$u['id'],'nombre'=>$u['nombre'],'email'=>$u['email'],'rol'=>$u['rol']];
    header('Location: dashboard.php');
    exit;
  }
  $error='credenciales';
}
require_once __DIR__.'/includes/header.php';
?>
<h2>Iniciar sesión</h2>
<?php if ($error): ?><p class="msg error">Error: verifique sus credenciales.</p><?php endif; ?>
<form method="post" class="form">
  <label>Email <input type="email" name="email" required></label>
  <label>Contraseña <input type="password" name="pass" required></label>
  <button>Ingresar</button>
</form>
<p>Usuarios terminal: <code>admin@terminal.com</code>, <code>empresa@terminal.com</code>, <code>cliente@terminal.com</code> (clave: <code>123456</code>).</p>
<?php require_once __DIR__.'/includes/footer.php'; ?>