<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';

$rutas = $pdo->query('SELECT id, origen, destino, costo_base FROM rutas WHERE activo = 1 ORDER BY origen, destino')->fetchAll();

$horarios = $pdo->query(
    'SELECT h.id, h.ruta_id, h.salida, h.llegada, r.origen, r.destino, r.costo_base,
            COALESCE(d.cupos_disponibles, 0) AS cupos
     FROM horarios h
     JOIN rutas r ON r.id = h.ruta_id
     LEFT JOIN disponibilidades d ON d.horario_id = h.id
     WHERE r.activo = 1
     ORDER BY r.origen, h.salida'
)->fetchAll();

$total        = null;
$cotizacionId = null;
$rutaSel      = null;
$personas     = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $rutaId    = (int) ($_POST['ruta_id'] ?? 0);
    $horarioId = (int) ($_POST['horario_id'] ?? 0);
    $personas  = max(1, (int) ($_POST['personas'] ?? 1));

    // El horario debe existir y pertenecer a la ruta elegida.
    $stmt = $pdo->prepare(
        'SELECT h.id, h.salida, h.llegada, r.id AS ruta_id, r.origen, r.destino, r.costo_base,
                COALESCE(d.cupos_disponibles, 0) AS cupos
         FROM horarios h
         JOIN rutas r ON r.id = h.ruta_id
         LEFT JOIN disponibilidades d ON d.horario_id = h.id
         WHERE h.id = ? AND r.id = ?'
    );
    $stmt->execute([$horarioId, $rutaId]);
    $rutaSel = $stmt->fetch();

    if (!$rutaSel) {
        $total = false; // bandera de error
    } elseif ((int) $rutaSel['cupos'] < $personas) {
        $total = false;
        $error = 'No hay cupos suficientes para ese horario. Disponibles: ' . (int) $rutaSel['cupos'] . '.';
    } else {
        $total = (float) $rutaSel['costo_base'] * $personas;

        if (isLogged()) {
            $stmt = $pdo->prepare(
                'INSERT INTO cotizaciones (usuario_id, ruta_id, horario_id, personas, total)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([user()['id'], $rutaId, $horarioId, $personas, $total]);
            $cotizacionId = (int) $pdo->lastInsertId();
        }
    }

    if ($total === false) {
        if (!isset($error)) {
            $error = 'La ruta o el horario seleccionado no existe.';
        }
    }
}
?>
<div class="page-head">
  <div>
    <h2>Cotizar transporte</h2>
    <p class="muted">Selecciona ruta y horario, indica cuántos viajan y obtén el total al instante.</p>
  </div>
</div>

<form method="post" class="form grid">
  <?= csrf_field() ?>
  <label>Ruta
    <select name="ruta_id" id="rutaSel" required>
      <option value="">Seleccione una ruta</option>
      <?php foreach ($rutas as $r): ?>
        <option value="<?= (int) $r['id'] ?>" <?= (isset($_POST['ruta_id']) && (int) $_POST['ruta_id'] === (int) $r['id']) ? 'selected' : '' ?>>
          <?= e($r['origen']) ?> → <?= e($r['destino']) ?> (<?= money($r['costo_base']) ?>)
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <label>Horario / Itinerario
    <select name="horario_id" id="horarioSel" required>
      <option value="">Seleccione primero una ruta</option>
      <?php foreach ($horarios as $h): ?>
        <option value="<?= (int) $h['id'] ?>" data-ruta="<?= (int) $h['ruta_id'] ?>"
                <?= (isset($_POST['horario_id']) && (int) $_POST['horario_id'] === (int) $h['id']) ? 'selected' : '' ?>>
          <?= e($h['salida']) ?>–<?= e($h['llegada']) ?> · cupos: <?= (int) $h['cupos'] ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <label>Personas / Cupos
    <input type="number" name="personas" min="1" value="<?= (int) ($_POST['personas'] ?? 1) ?>" required>
  </label>

  <div class="form-full">
    <button type="submit" class="btn btn-primary">Calcular total</button>
  </div>
</form>

<?php if (isset($error)): ?>
  <div class="msg error"><?= e($error) ?></div>
<?php endif; ?>

<?php if (is_numeric($total)): ?>
  <div class="msg ok">
    🛣 <strong>Ruta:</strong> <?= e($rutaSel['origen']) ?> → <?= e($rutaSel['destino']) ?><br>
    🕒 <strong>Horario:</strong> <?= e($rutaSel['salida']) ?> – <?= e($rutaSel['llegada']) ?><br>
    👥 <strong>Personas:</strong> <?= (int) $personas ?><br>
    💰 <strong>Total estimado:</strong> <?= money($total) ?>
  </div>

  <?php if (isLogged()): ?>
    <?php if (is_role('cliente')): ?>
      <form action="comprar.php" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="cotizacion_id" value="<?= (int) $cotizacionId ?>">
        <button type="submit" class="btn btn-accent">Comprar tiquetes</button>
      </form>
    <?php else: ?>
      <p class="msg info">Tu rol permite vender en taquilla. Usa <a href="venta.php">Taquilla</a> para emitir tiquetes.</p>
    <?php endif; ?>
  <?php else: ?>
    <p class="msg info">Para comprar tus tiquetes, <a href="login.php">inicia sesión</a> como cliente.</p>
  <?php endif; ?>
<?php endif; ?>

<script>
  (function () {
    const ruta = document.getElementById('rutaSel');
    const horario = document.getElementById('horarioSel');
    if (!ruta || !horario) return;

    const allOptions = Array.from(horario.querySelectorAll('option[data-ruta]'));

    function filtrar() {
      const val = ruta.value;
      let visibles = 0;
      allOptions.forEach(function (opt) {
        const coincide = opt.dataset.ruta === val;
        opt.hidden = val !== '' && !coincide;
        opt.disabled = val !== '' && !coincide;
        if (coincide) visibles++;
      });
      if (val !== '' && !horario.value || (horario.selectedOptions[0] && horario.selectedOptions[0].disabled)) {
        horario.value = '';
      }
    }
    ruta.addEventListener('change', filtrar);
    filtrar();
  })();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
