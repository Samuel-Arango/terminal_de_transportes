<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
requireRole(['admin', 'cliente', 'empresa']);
require_once __DIR__ . '/includes/header.php';

$u = user();

$stats = [
    'rutas'    => (int) $pdo->query('SELECT COUNT(*) FROM rutas')->fetchColumn(),
    'empresas' => (int) $pdo->query('SELECT COUNT(*) FROM empresas')->fetchColumn(),
    'vehiculos'=> (int) $pdo->query('SELECT COUNT(*) FROM vehiculos')->fetchColumn(),
];

$misCompras = 0;
$misTiquetes = 0;
if (is_role('cliente')) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM compras WHERE usuario_id = ?');
    $stmt->execute([$u['id']]);
    $misCompras = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM tiquetes t
         JOIN compras c ON c.id = t.compra_id
         WHERE c.usuario_id = ?'
    );
    $stmt->execute([$u['id']]);
    $misTiquetes = (int) $stmt->fetchColumn();
}
?>
<div class="page-head">
  <div>
    <h2>Hola, <?= e($u['nombre']) ?> 👋</h2>
    <p class="muted">Rol: <span class="badge info"><?= e($u['rol']) ?></span></p>
  </div>
</div>

<div class="stat-grid">
  <div class="stat"><div class="stat-value"><?= $stats['rutas'] ?></div><div class="stat-label">Rutas activas</div></div>
  <div class="stat"><div class="stat-value"><?= $stats['empresas'] ?></div><div class="stat-label">Empresas vinculadas</div></div>
  <div class="stat"><div class="stat-value"><?= $stats['vehiculos'] ?></div><div class="stat-label">Vehículos</div></div>
  <?php if (is_role('cliente')): ?>
    <div class="stat"><div class="stat-value"><?= $misTiquetes ?></div><div class="stat-label">Mis tiquetes</div></div>
  <?php endif; ?>
</div>

<h3>Acciones rápidas</h3>
<div class="actions">
  <a class="btn btn-primary" href="cotizar.php">Cotizar viaje</a>
  <a class="btn btn-outline" href="rutas.php">Ver rutas</a>
  <?php if (is_role('cliente')): ?>
    <a class="btn btn-outline" href="tiquetes.php">Mis tiquetes</a>
  <?php endif; ?>
  <?php if (is_role('admin', 'empresa')): ?>
    <a class="btn btn-outline" href="venta.php">Taquilla (vender)</a>
    <a class="btn btn-outline" href="validar.php">Validar en puerta</a>
    <a class="btn btn-outline" href="horarios.php">Horarios</a>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
