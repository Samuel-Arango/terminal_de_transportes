<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/header.php';

$compraId = (int) ($_GET['compra'] ?? 0);
$u        = user();
$esStaff  = is_role('admin', 'empresa');

$stmt = $pdo->prepare(
    'SELECT c.id, c.usuario_id, c.cantidad, c.total, c.metodo_pago, c.canal, c.estado AS compra_estado,
            c.fecha_compra,
            h.salida, h.llegada,
            r.origen, r.destino, r.costo_base,
            e.nombre AS empresa, v.tipo AS vehiculo, v.placa
     FROM compras c
     JOIN horarios h ON h.id = c.horario_id
     JOIN rutas r    ON r.id = h.ruta_id
     JOIN empresas e ON e.id = r.empresa_id
     JOIN vehiculos v ON v.id = h.vehiculo_id
     WHERE c.id = ?'
);
$stmt->execute([$compraId]);
$compra = $stmt->fetch();

if (!$compra || (!$esStaff && (int) $compra['usuario_id'] !== (int) $u['id'])) {
    echo '<p class="msg error">La compra no existe o no tienes permiso para verla.</p>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$stmt = $pdo->prepare('SELECT codigo, pasajero_nombre, pasajero_doc, asiento, estado FROM tiquetes WHERE compra_id = ? ORDER BY asiento');
$stmt->execute([$compraId]);
$tiquetes = $stmt->fetchAll();
?>
<div class="page-head">
  <div>
    <h2>Comprobante de compra #<?= (int) $compra['id'] ?></h2>
    <p class="muted">
      <?= e($compra['origen']) ?> → <?= e($compra['destino']) ?> ·
      <?= e($compra['salida']) ?>–<?= e($compra['llegada']) ?> ·
      <?= e($compra['empresa']) ?>
    </p>
  </div>
  <span class="badge ok"><?= e($compra['compra_estado']) ?></span>
</div>

<div class="stat-grid">
  <div class="stat"><div class="stat-value"><?= (int) $compra['cantidad'] ?></div><div class="stat-label">Tiquetes</div></div>
  <div class="stat"><div class="stat-value"><?= money($compra['total']) ?></div><div class="stat-label">Total pagado</div></div>
  <div class="stat"><div class="stat-value"><?= e(ucfirst($compra['canal'])) ?></div><div class="stat-label">Canal</div></div>
  <div class="stat"><div class="stat-value"><?= e($compra['metodo_pago']) ?></div><div class="stat-label">Método de pago</div></div>
</div>

<?php foreach ($tiquetes as $t): ?>
  <?php $qr = 'https://quickchart.io/qr?size=220&margin=1&text=' . urlencode('TT|' . $t['codigo']); ?>
  <div class="ticket">
    <div>
      <h3>🎫 Tiquete <?= e($t['codigo']) ?></h3>
      <div class="ticket-meta">
        <span><strong>Pasajero:</strong> <?= e($t['pasajero_nombre'] ?: '—') ?></span>
        <span><strong>Documento:</strong> <?= e($t['pasajero_doc'] ?: '—') ?></span>
        <span><strong>Ruta:</strong> <?= e($compra['origen']) ?> → <?= e($compra['destino']) ?></span>
        <span><strong>Salida:</strong> <?= e($compra['salida']) ?> · <strong>Llegada:</strong> <?= e($compra['llegada']) ?></span>
        <span><strong>Asiento:</strong> <?= (int) $t['asiento'] ?> · <strong>Vehículo:</strong> <?= e($compra['vehiculo']) ?> (<?= e($compra['placa']) ?>)</span>
        <span><strong>Estado:</strong> <span class="badge <?= $t['estado'] === 'valido' ? 'ok' : 'error' ?>"><?= e($t['estado']) ?></span></span>
      </div>
    </div>
    <div class="qr">
      <img src="<?= e($qr) ?>" alt="Código QR del tiquete <?= e($t['codigo']) ?>" loading="lazy">
      <div class="ticket-code" style="text-align:center;margin-top:6px;"><?= e($t['codigo']) ?></div>
    </div>
  </div>
<?php endforeach; ?>

<div class="actions">
  <button class="btn btn-primary" onclick="window.print()">Imprimir</button>
  <a class="btn btn-outline" href="tiquetes.php">Mis tiquetes</a>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
