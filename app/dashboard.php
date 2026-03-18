<?php


require_once __DIR__.'/includes/db.php';
require_once __DIR__.'/includes/auth.php';
requireRole(['admin','cliente','empresa']); 
require_once __DIR__.'/includes/header.php';
?>
<h2>Panel de usuario</h2>
<?php $u = user() ?? []; ?>
<p>Hola, <strong><?= htmlspecialchars($u['nombre'] ?? 'Usuario', ENT_QUOTES, 'UTF-8') ?></strong>
   (rol: <?= htmlspecialchars($u['rol'] ?? '—', ENT_QUOTES, 'UTF-8') ?>).</p>

<ul>
  <li><a href="rutas.php">Gestionar / ver rutas</a></li>
  <li><a href="cotizar.php">Realizar una cotización</a></li>
  <li><a href="logout.php">Cerrar sesión</a></li>
</ul>
<?php require_once __DIR__.'/includes/footer.php'; ?>