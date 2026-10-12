<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
requireRole(['admin', 'empresa']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'nuevo_horario') {
        $rutaId     = (int) ($_POST['ruta_id'] ?? 0);
        $vehiculoId = (int) ($_POST['vehiculo_id'] ?? 0);
        $salida     = trim($_POST['salida'] ?? '');
        $llegada    = trim($_POST['llegada'] ?? '');

        if ($rutaId && $vehiculoId && $salida && $llegada) {
            $pdo->prepare('INSERT INTO horarios (ruta_id, vehiculo_id, salida, llegada) VALUES (?, ?, ?, ?)')
                ->execute([$rutaId, $vehiculoId, $salida, $llegada]);
            flash('ok', 'Horario creado correctamente.');
        } else {
            flash('error', 'Debes completar todos los campos del horario.');
        }
    } elseif ($accion === 'editar_disp') {
        $horarioId = (int) ($_POST['horario_id'] ?? 0);
        $cupos     = (int) ($_POST['cupos'] ?? 0);

        if ($horarioId && $cupos >= 0) {
            $stmt = $pdo->prepare('SELECT id FROM disponibilidades WHERE horario_id = ? LIMIT 1');
            $stmt->execute([$horarioId]);
            if ($dispId = $stmt->fetchColumn()) {
                $pdo->prepare('UPDATE disponibilidades SET cupos_totales = ?, cupos_disponibles = ? WHERE id = ?')
                    ->execute([$cupos, $cupos, $dispId]);
                flash('ok', 'Disponibilidad actualizada.');
            } else {
                $pdo->prepare('INSERT INTO disponibilidades (horario_id, cupos_totales, cupos_disponibles) VALUES (?, ?, ?)')
                    ->execute([$horarioId, $cupos, $cupos]);
                flash('ok', 'Disponibilidad registrada.');
            }
        } else {
            flash('error', 'Datos inválidos al registrar disponibilidad.');
        }
    } elseif ($accion === 'eliminar') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('DELETE FROM horarios WHERE id = ?')->execute([$id]);
            flash('ok', 'Horario eliminado.');
        }
    }

    header('Location: horarios.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';

$rutas     = $pdo->query('SELECT id, origen, destino FROM rutas ORDER BY id DESC')->fetchAll();
$vehiculos = $pdo->query('SELECT id, tipo, placa FROM vehiculos ORDER BY id DESC')->fetchAll();

$horarios = $pdo->query(
    'SELECT h.id, r.origen, r.destino, v.tipo AS vehiculo, v.placa, h.salida, h.llegada,
            COALESCE(d.cupos_disponibles, 0) AS cupos, d.cupos_totales
     FROM horarios h
     JOIN rutas r ON r.id = h.ruta_id
     JOIN vehiculos v ON v.id = h.vehiculo_id
     LEFT JOIN disponibilidades d ON d.horario_id = h.id
     ORDER BY h.id DESC'
)->fetchAll();
?>
<div class="page-head">
  <div>
    <h2>Gestión de horarios</h2>
    <p class="muted">Programa salidas y administra los cupos disponibles.</p>
  </div>
</div>

<table class="tbl">
  <thead>
    <tr><th>#</th><th>Ruta</th><th>Vehículo</th><th>Salida</th><th>Llegada</th><th>Cupos</th><th></th></tr>
  </thead>
  <tbody>
    <?php foreach ($horarios as $h): ?>
      <tr>
        <td><?= (int) $h['id'] ?></td>
        <td><?= e($h['origen']) ?> → <?= e($h['destino']) ?></td>
        <td><?= e($h['vehiculo']) ?> (<?= e($h['placa']) ?>)</td>
        <td><?= e($h['salida']) ?></td>
        <td><?= e($h['llegada']) ?></td>
        <td><?= (int) $h['cupos'] ?> / <?= (int) $h['cupos_totales'] ?></td>
        <td>
          <form method="post" onsubmit="return confirm('¿Eliminar horario?');" style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="id" value="<?= (int) $h['id'] ?>">
            <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<h3>Registrar nuevo horario</h3>
<form method="post" class="form grid">
  <?= csrf_field() ?>
  <input type="hidden" name="accion" value="nuevo_horario">
  <label>Ruta
    <select name="ruta_id" required>
      <option value="">Seleccione una ruta</option>
      <?php foreach ($rutas as $r): ?>
        <option value="<?= (int) $r['id'] ?>"><?= e($r['origen']) ?> → <?= e($r['destino']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Vehículo
    <select name="vehiculo_id" required>
      <option value="">Seleccione un vehículo</option>
      <?php foreach ($vehiculos as $v): ?>
        <option value="<?= (int) $v['id'] ?>"><?= e($v['tipo']) ?> (<?= e($v['placa']) ?>)</option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Salida <input type="time" name="salida" required></label>
  <label>Llegada <input type="time" name="llegada" required></label>
  <div class="form-full"><button class="btn btn-primary">Guardar horario</button></div>
</form>

<h3>Registrar / actualizar disponibilidad</h3>
<form method="post" class="form grid">
  <?= csrf_field() ?>
  <input type="hidden" name="accion" value="editar_disp">
  <label>Horario
    <select name="horario_id" required>
      <option value="">Seleccione horario</option>
      <?php foreach ($horarios as $h): ?>
        <option value="<?= (int) $h['id'] ?>">
          #<?= (int) $h['id'] ?> · <?= e($h['origen']) ?> → <?= e($h['destino']) ?> (<?= e($h['salida']) ?>)
        </option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Cupos totales <input type="number" name="cupos" min="0" required></label>
  <div class="form-full"><button class="btn btn-primary">Guardar disponibilidad</button></div>
</form>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
