<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/header.php';

$u       = user();
$esStaff = is_role('admin', 'empresa');

$sql =
    'SELECT c.id AS compra_id, c.fecha_compra, c.total, c.cantidad, c.canal, c.estado,
            h.salida, h.llegada, r.origen, r.destino, e.nombre AS empresa,
            (SELECT COUNT(*) FROM tiquetes t WHERE t.compra_id = c.id) AS n_tiquetes
     FROM compras c
     JOIN horarios h ON h.id = c.horario_id
     JOIN rutas r    ON r.id = h.ruta_id
     JOIN empresas e ON e.id = r.empresa_id';

$params = [];
if (!$esStaff) {
    $sql .= ' WHERE c.usuario_id = ?';
    $params[] = (int) $u['id'];
}
$sql .= ' ORDER BY c.fecha_compra DESC LIMIT 100';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$compras = $stmt->fetchAll();
?>
<div class="page-head">
  <div>
    <h2><?= $esStaff ? 'Ventas y tiquetes emitidos' : 'Mis tiquetes' ?></h2>
    <p class="muted"><?= $esStaff ? 'Últimas compras y ventas en taquilla.' : 'Historial de tus compras.' ?></p>
  </div>
  <?php if (is_role('admin', 'empresa')): ?>
    <a class="btn btn-accent" href="venta.php">Nueva venta</a>
  <?php endif; ?>
</div>

<?php if (!$compras): ?>
  <p class="msg info">Aún no hay compras registradas.</p>
<?php else: ?>
  <table class="tbl">
    <thead>
      <tr>
        <th>#</th>
        <th>Fecha</th>
        <th>Ruta</th>
        <th>Salida</th>
        <th>Tiquetes</th>
        <th>Total</th>
        <th>Canal</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($compras as $c): ?>
        <tr>
          <td><?= (int) $c['compra_id'] ?></td>
          <td><?= e(date('d/m/Y H:i', strtotime($c['fecha_compra']))) ?></td>
          <td><?= e($c['origen']) ?> → <?= e($c['destino']) ?> <span class="muted">(<?= e($c['empresa']) ?>)</span></td>
          <td><?= e($c['salida']) ?>–<?= e($c['llegada']) ?></td>
          <td><?= (int) $c['n_tiquetes'] ?></td>
          <td><?= money($c['total']) ?></td>
          <td><span class="badge <?= $c['canal'] === 'web' ? 'info' : 'warn' ?>"><?= e($c['canal']) ?></span></td>
          <td><a href="ver_tiquete.php?compra=<?= (int) $c['compra_id'] ?>">Ver tiquetes</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
