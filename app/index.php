<?php require_once __DIR__.'/includes/header.php'; ?>
<h1>Terminal de Transportes</h1>
<p>Prototipo dinámico equivalente a la “Terminal de Transportes”. Aquí centralizamos:
información del centro, empresas vinculadas, vehículos, rutas, horarios, disponibilidad,
costos, cotización y compra.</p>
<section class="cards">
  <a class="card" href="rutas.php">Ver Rutas</a>
  <a class="card" href="cotizar.php">Cotizar</a>
  <?php if (isLogged()): ?>
    <a class="card" href="dashboard.php">Panel</a>
  <?php else: ?>
    <a class="card" href="login.php">Ingresar</a>
  <?php endif; ?>
</section>
<?php require_once __DIR__.'/includes/footer.php'; ?>
