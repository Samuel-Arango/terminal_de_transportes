<?php require_once __DIR__ . '/includes/header.php'; ?>
<section class="hero">
  <span class="hero-kicker">Bienvenido</span>
  <h1>Tu viaje comienza aquí</h1>
  <p>Consulta rutas y horarios, cotiza tu trayecto y compra tus tiquetes en línea de forma
     rápida, segura y sin filas. Todo desde un solo lugar.</p>
  <div class="hero-actions">
    <a class="btn btn-accent" href="cotizar.php">Cotizar mi viaje</a>
    <a class="btn btn-outline" href="rutas.php" style="background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.5);color:#fff">Ver rutas</a>
  </div>
</section>

<section class="cards">
  <a class="card" href="rutas.php">
    <span class="card-icon">🛣️</span>
    <strong>Rutas y destinos</strong>
    <span>Explora las rutas y empresas disponibles.</span>
  </a>
  <a class="card" href="cotizar.php">
    <span class="card-icon">🧮</span>
    <strong>Cotizar</strong>
    <span>Calcula el costo de tu viaje por pasajero.</span>
  </a>
  <a class="card" href="<?= isLogged() ? 'tiquetes.php' : 'login.php' ?>">
    <span class="card-icon">🎫</span>
    <strong><?= isLogged() ? 'Mis tiquetes' : 'Ingresar' ?></strong>
    <span><?= isLogged() ? 'Revisa tus compras y tiquetes.' : 'Accede para comprar tiquetes.' ?></span>
  </a>
  <a class="card" href="<?= isLogged() ? 'dashboard.php' : 'login.php' ?>">
    <span class="card-icon">📊</span>
    <strong>Panel</strong>
    <span>Gestiona la operación del terminal.</span>
  </a>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
