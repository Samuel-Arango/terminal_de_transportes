<?php
require_once __DIR__ . '/auth.php';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Terminal de Transporte</title>
  <!-- Asegúrate de que esta ruta existe: app/css/styles.css -->
  <link rel="stylesheet" href="/css/estilo.css">
</head>
<body>
  <header class="topbar">
    <!-- Verifica la ruta del logo: si la imagen está en app/Images usa "Images/logo_terminal.png" -->
    <img src="/Images/logo1.jpg" alt="Logo Terminal" class="logo" loading="lazy">
    <nav aria-label="Navegación principal">
      <a href="index.php">Inicio</a>
      <a href="rutas.php">Rutas</a>
      <a href="cotizar.php">Cotizar</a>


      <?php if (isLogged()): 
        $u = user();
      ?>
        <?php if (in_array($u['rol'] ?? '', ['admin', 'empresa'], true)): ?>
          <a href="empresas.php">Empresas</a>
          <a href="vehiculos.php">Vehículos</a>
          <a href="horarios.php">Horarios</a>
        <?php endif; ?>

        <a href="dashboard.php">Panel</a>
        <a href="logout.php">Salir</a>
      <?php else: ?>
        <a href="login.php">Ingresar</a>
      <?php endif; ?>
    </nav>
  </header>

  <main class="container">