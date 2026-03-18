<?php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
requireRole(['admin', 'empresa']);
require_once __DIR__ . '/includes/header.php';


$rutas = $pdo->query("SELECT id, origen, destino FROM rutas ORDER BY id DESC")->fetchAll();
$vehiculos = $pdo->query("SELECT id, tipo, placa FROM vehiculos ORDER BY id DESC")->fetchAll();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'nuevo_horario') {
    $ruta_id     = (int)($_POST['ruta_id'] ?? 0);
    $vehiculo_id = (int)($_POST['vehiculo_id'] ?? 0);
    $salida      = trim($_POST['salida'] ?? '');
    $llegada     = trim($_POST['llegada'] ?? '');

    if ($ruta_id && $vehiculo_id && $salida && $llegada) {
        $stmt = $pdo->prepare("INSERT INTO horarios (ruta_id, vehiculo_id, salida, llegada) VALUES (?, ?, ?, ?)");
        $stmt->execute([$ruta_id, $vehiculo_id, $salida, $llegada]);
        echo '<p class="msg ok">✅ Horario creado correctamente.</p>';
    } else {
        echo '<p class="msg error">⚠️ Debe completar todos los campos del horario.</p>';
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'nueva_disp') {
    $horario_id = (int)($_POST['horario_id'] ?? 0);
    $cupos      = (int)($_POST['cupos'] ?? 0);

    if ($horario_id && $cupos > 0) {
        $stmt = $pdo->prepare("INSERT INTO disponibilidades (horario_id, cupos_disponibles) VALUES (?, ?)");
        $stmt->execute([$horario_id, $cupos]);
        echo '<p class="msg ok">✅ Disponibilidad registrada correctamente.</p>';
    } else {
        echo '<p class="msg error">⚠️ Datos inválidos al registrar disponibilidad.</p>';
    }
}


$horarios = $pdo->query("
    SELECT 
        h.id, 
        r.origen, 
        r.destino, 
        v.tipo AS vehiculo, 
        v.placa, 
        h.salida, 
        h.llegada
    FROM horarios h
    JOIN rutas r ON r.id = h.ruta_id
    JOIN vehiculos v ON v.id = h.vehiculo_id
    ORDER BY h.id DESC
")->fetchAll();


$horariosSelect = $pdo->query("SELECT id FROM horarios ORDER BY id DESC")->fetchAll();
?>

<h2>Gestión de Horarios</h2>

<table class="tbl">
  <thead>
    <tr>
      <th>#</th>
      <th>Ruta</th>
      <th>Vehículo</th>
      <th>Salida</th>
      <th>Llegada</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($horarios as $h): ?>
      <tr>
        <td><?= $h['id'] ?></td>
        <td><?= htmlspecialchars($h['origen']) ?> → <?= htmlspecialchars($h['destino']) ?></td>
        <td><?= htmlspecialchars($h['vehiculo']) ?> (<?= htmlspecialchars($h['placa']) ?>)</td>
        <td><?= $h['salida'] ?></td>
        <td><?= $h['llegada'] ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<h3>Registrar nuevo horario</h3>
<form method="post" class="form grid">
  <input type="hidden" name="accion" value="nuevo_horario">
  <label>Ruta
    <select name="ruta_id" required>
      <option value="">Seleccione una ruta</option>
      <?php foreach ($rutas as $r): ?>
        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['origen']) ?> → <?= htmlspecialchars($r['destino']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>

  <label>Vehículo
    <select name="vehiculo_id" required>
      <option value="">Seleccione un vehículo</option>
      <?php foreach ($vehiculos as $v): ?>
        <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['tipo']) ?> (<?= htmlspecialchars($v['placa']) ?>)</option>
      <?php endforeach; ?>
    </select>
  </label>

  <label>Salida <input type="time" name="salida" required></label>
  <label>Llegada <input type="time" name="llegada" required></label>
  <button>Guardar horario</button>
</form>

<h3>Registrar disponibilidad (cupos)</h3>
<form method="post" class="form grid">
  <input type="hidden" name="accion" value="nueva_disp">
  <label>Horario
    <select name="horario_id" required>
      <option value="">Seleccione horario</option>
      <?php foreach ($horariosSelect as $h): ?>
        <option value="<?= $h['id'] ?>">Horario #<?= $h['id'] ?></option>
      <?php endforeach; ?>
    </select>
  </label>

  <label>Cupos disponibles <input type="number" name="cupos" min="1" required></label>
  <button>Guardar disponibilidad</button>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>




