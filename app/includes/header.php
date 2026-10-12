<?php
require_once __DIR__ . '/auth.php';
enforce_idle_timeout();

$current = basename($_SERVER['PHP_SELF'] ?? '');
$u       = user();
$navLink = static function (string $file, string $label, string $current): string {
    $active = $current === $file ? ' class="active" aria-current="page"' : '';
    return '<a href="' . $file . '"' . $active . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
};
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(APP_NAME) ?></title>
  <meta name="description" content="Consulta rutas, cotiza y compra tiquetes de transporte.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/estilo.css">
</head>
<body>
  <header class="topbar">
    <a class="brand" href="index.php">
      <img src="../images/logo1.png" alt="Logotipo <?= e(APP_NAME) ?>" class="logo" loading="lazy">
      <span class="brand-text">
        <strong>Terminal</strong>
        <small>de Transportes</small>
      </span>
    </a>

    <nav aria-label="Navegación principal">
      <?= $navLink('index.php', 'Inicio', $current) ?>
      <?= $navLink('rutas.php', 'Rutas', $current) ?>
      <?= $navLink('cotizar.php', 'Cotizar', $current) ?>

      <?php if (is_role('admin', 'empresa')): ?>
        <?= $navLink('empresas.php', 'Empresas', $current) ?>
        <?= $navLink('vehiculos.php', 'Vehículos', $current) ?>
        <?= $navLink('horarios.php', 'Horarios', $current) ?>
        <?= $navLink('venta.php', 'Taquilla', $current) ?>
        <?= $navLink('validar.php', 'Validar', $current) ?>
      <?php endif; ?>

      <?php if (isLogged()): ?>
        <?= $navLink('tiquetes.php', 'Mis tiquetes', $current) ?>
        <?= $navLink('dashboard.php', 'Panel', $current) ?>
        <form action="logout.php" method="post" class="logout-form">
          <?= csrf_field() ?>
          <button type="submit" class="nav-cta">Salir</button>
        </form>
      <?php else: ?>
        <a href="login.php" class="nav-cta">Ingresar</a>
      <?php endif; ?>
    </nav>
  </header>

  <main class="container">
    <?php foreach (get_flashes() as $f): ?>
      <div class="msg <?= e($f['type']) ?>"><?= e($f['message']) ?></div>
    <?php endforeach; ?>
