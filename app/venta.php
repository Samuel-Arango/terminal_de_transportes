<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/tickets.php';
requireRole(['admin', 'empresa']);
require_once __DIR__ . '/includes/header.php';

$u = user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    try {
        $venta = vender_tiquetes($pdo, [
            'horario_id'      => (int) ($_POST['horario_id'] ?? 0),
            'cantidad'        => (int) ($_POST['cantidad'] ?? 1),
            'usuario_id'      => null,
            'vendedor_id'     => (int) $u['id'],
            'cotizacion_id'   => null,
            'metodo_pago'     => trim($_POST['metodo_pago'] ?? 'efectivo'),
            'canal'           => 'taquilla',
            'pasajero_nombre' => trim($_POST['pasajero_nombre'] ?? ''),
            'pasajero_doc'    => trim($_POST['pasajero_doc'] ?? ''),
        ]);
        flash('ok', 'Venta registrada. Se emitieron ' . count($venta['codigos']) . ' tiquete(s).');
        header('Location: ver_tiquete.php?compra=' . $venta['compra_id']);
        exit;
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
        header('Location: venta.php');
        exit;
    }
}

$horarios = $pdo->query(
    'SELECT h.id, h.salida, h.llegada, r.origen, r.destino, r.costo_base,
            COALESCE(d.cupos_disponibles, 0) AS cupos
     FROM horarios h
     JOIN rutas r ON r.id = h.ruta_id
     LEFT JOIN disponibilidades d ON d.horario_id = h.id
     WHERE r.activo = 1
     ORDER BY r.origen, h.salida'
)->fetchAll();

$recientes = $pdo->query(
    'SELECT c.id, c.fecha_compra, c.cantidad, c.total, c.metodo_pago,
            r.origen, r.destino
     FROM compras c
     JOIN horarios h ON h.id = c.horario_id
     JOIN rutas r ON r.id = h.ruta_id
     WHERE c.canal = "taquilla"
     ORDER BY c.fecha_compra DESC
     LIMIT 10'
)->fetchAll();
?>
<div class="page-head">
  <div>
    <h2>Taquilla · Venta de tiquetes</h2>
    <p class="muted">Emite tiquetes presenciales descontando cupos del horario.</p>
  </div>
</div>

<h3>Nueva venta</h3>
<form method="post" class="form grid">
  <?= csrf_field() ?>
  <label class="form-full">Horario / Itinerario
    <select name="horario_id" required>
      <option value="">Seleccione un horario</option>
      <?php foreach ($horarios as $h): ?>
        <option value="<?= (int) $h['id'] ?>" <?= (int) $h['cupos'] <= 0 ? 'disabled' : '' ?>>
          <?= e($h['origen']) ?> → <?= e($h['destino']) ?> · <?= e($h['salida']) ?>–<?= e($h['llegada']) ?>
          · <?= money($h['costo_base']) ?> · cupos: <?= (int) $h['cupos'] ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <label>Cantidad de tiquetes
    <input type="number" name="cantidad" min="1" value="1" required>
  </label>

  <label>Método de pago
    <select name="metodo_pago">
      <option value="efectivo">Efectivo</option>
      <option value="tarjeta">Tarjeta</option>
      <option value="transferencia">Transferencia</option>
    </select>
  </label>

  <label>Nombre del pasajero (opcional)
    <input name="pasajero_nombre" placeholder="Nombre completo">
  </label>

  <label>Documento del pasajero (opcional)
    <input name="pasajero_doc" placeholder="C.C. / Pasaporte">
  </label>

  <div class="form-full">
    <button type="submit" class="btn btn-accent">Registrar venta y emitir tiquetes</button>
  </div>
</form>

<h3>Últimas ventas en taquilla</h3>
<?php if (!$recientes): ?>
  <p class="msg info">Todavía no hay ventas en taquilla.</p>
<?php else: ?>
  <table class="tbl">
    <thead>
      <tr><th>#</th><th>Fecha</th><th>Ruta</th><th>Tiquetes</th><th>Total</th><th>Pago</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($recientes as $r): ?>
        <tr>
          <td><?= (int) $r['id'] ?></td>
          <td><?= e(date('d/m/Y H:i', strtotime($r['fecha_compra']))) ?></td>
          <td><?= e($r['origen']) ?> → <?= e($r['destino']) ?></td>
          <td><?= (int) $r['cantidad'] ?></td>
          <td><?= money($r['total']) ?></td>
          <td><?= e($r['metodo_pago']) ?></td>
          <td><a href="ver_tiquete.php?compra=<?= (int) $r['id'] ?>">Ver</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
